<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace auth_musaml\local;

use auth_musaml\local\provider\base as provider;
use core\context\system;
use core\exception\coding_exception;
use core\exception\moodle_exception;
use core\url;
use stdClass;

/**
 * Identity provider helper.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp {
    /** @var int metadata is refreshed opportunistically after this many seconds */
    public const REFRESH_INTERVAL = DAYSECS;

    /** @var int minimum gap between failed refresh attempts */
    public const REFRESH_RETRY = HOURSECS;

    /** @var int metadata download limit in bytes */
    public const METADATA_MAXSIZE = 2 * 1024 * 1024;

    /**
     * Fetch IDP record.
     *
     * @param int $id
     * @return stdClass|null
     */
    public static function fetch(int $id): ?stdClass {
        global $DB;
        $record = $DB->get_record('auth_musaml_idp', ['id' => $id]);
        return $record ?: null;
    }

    /**
     * Fetch IDP record by entity ID.
     *
     * @param string $entityid
     * @return stdClass|null
     */
    public static function fetch_by_entityid(string $entityid): ?stdClass {
        global $DB;
        $record = $DB->get_record('auth_musaml_idp', ['entityid' => $entityid]);
        return $record ?: null;
    }

    /**
     * All IDP records.
     *
     * @return stdClass[]
     */
    public static function get_all(): array {
        global $DB;
        return $DB->get_records('auth_musaml_idp', [], 'name ASC, id ASC');
    }

    /**
     * Enabled IDPs usable in the given tenant.
     *
     * @param int|null $tenantid current tenant, null means no tenant
     * @return stdClass[]
     */
    public static function get_enabled(?int $tenantid): array {
        global $DB;

        if ($tenantid && self::is_tenant_archived($tenantid)) {
            // Identity providers of an archived tenant are not offered any more.
            $tenantid = null;
        }
        if ($tenantid) {
            $select = "enabled = 1 AND (tenantid IS NULL OR tenantid = :tenantid)";
            $params = ['tenantid' => $tenantid];
        } else {
            $select = "enabled = 1 AND tenantid IS NULL";
            $params = [];
        }
        return $DB->get_records_select('auth_musaml_idp', $select, $params, 'name ASC, id ASC');
    }

    /**
     * Download IDP metadata XML.
     *
     * @param string $url
     * @return string XML
     * @throws moodle_exception on any download problem
     */
    public static function download_metadata(string $url): string {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) {
            throw new moodle_exception('error_metadataurl', 'auth_musaml', '', $url);
        }
        $security = new \core\files\curl_security_helper();
        if ($security->url_is_blocked($url)) {
            throw new moodle_exception('error_metadatablocked', 'auth_musaml', '', $url);
        }
        $response = download_file_content($url, null, null, true, 30, 10);
        if ($response->status != 200) {
            $error = $response->error ?: ($response->status . ' ' . $response->response_code);
            throw new moodle_exception('error_metadatadownload', 'auth_musaml', '', $error);
        }
        if (strlen($response->results) > self::METADATA_MAXSIZE) {
            throw new moodle_exception('error_metadatadownload', 'auth_musaml', '', 'too large');
        }
        return $response->results;
    }

    /**
     * Download and parse IDP metadata.
     *
     * @param string $url
     * @return array see saml::parse_idp_metadata()
     * @throws moodle_exception
     */
    public static function load_metadata(string $url): array {
        $xml = self::download_metadata($url);
        return saml::parse_idp_metadata($xml);
    }

    /**
     * Parse metadata given as a URL or as the XML itself.
     *
     * Some products, Google among them, offer a file download and no metadata URL.
     *
     * @param string $source URL or metadata XML
     * @return array see saml::parse_idp_metadata()
     * @throws moodle_exception
     */
    public static function load_metadata_source(string $source): array {
        $source = trim($source);
        if (self::source_is_url($source)) {
            return self::load_metadata($source);
        }
        if (strlen($source) > self::METADATA_MAXSIZE) {
            throw new moodle_exception('error_metadatadownload', 'auth_musaml', '', 'too large');
        }
        return saml::parse_idp_metadata($source);
    }

    /**
     * Is the metadata source a URL rather than XML?
     *
     * @param string $source
     * @return bool
     */
    public static function source_is_url(string $source): bool {
        return (bool)preg_match('#^https?://#i', trim($source));
    }

    /**
     * Decoded certjson with defaults.
     *
     * @param stdClass $idp
     * @return array
     */
    public static function get_certinfo(stdClass $idp): array {
        $defaults = [
            'certs' => [],
            'extracerts' => [],
            'nameidformat' => null,
            'attributes' => [],
            'validuntil' => null,
            'fetched' => null,
            'attempted' => null,
            'error' => null,
            'autorefresh' => 1,
        ];
        $info = json_decode((string)$idp->certjson, true);
        if (!is_array($info)) {
            $info = [];
        }
        return array_merge($defaults, $info);
    }

    /**
     * Store certificate handling settings and extra certificates.
     *
     * @param stdClass $idp
     * @param bool $autorefresh
     * @param string $extracerts PEM or bare base64 certificates, one after another
     * @return stdClass updated record
     * @throws moodle_exception when a certificate cannot be parsed
     */
    public static function update_certinfo(stdClass $idp, bool $autorefresh, string $extracerts): stdClass {
        global $DB;

        $info = self::get_certinfo($idp);
        $info['autorefresh'] = (int)$autorefresh;
        $info['extracerts'] = [];

        foreach (self::split_certificates($extracerts) as $cert) {
            $pem = self::to_pem($cert);
            $parsed = openssl::parse_cert($pem);
            if (!$parsed) {
                throw new moodle_exception('error_certificate', 'auth_musaml');
            }
            $info['extracerts'][] = [
                'cert' => $cert,
                'subject' => $parsed['subject'],
                'notafter' => $parsed['notafter'],
                'fingerprint' => $parsed['fingerprint'],
            ];
        }

        $idp->certjson = json_encode($info);
        $DB->set_field('auth_musaml_idp', 'certjson', $idp->certjson, ['id' => $idp->id]);

        return self::fetch($idp->id);
    }

    /**
     * Split pasted text into bare base64 certificates.
     *
     * @param string $text
     * @return string[]
     */
    public static function split_certificates(string $text): array {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        if (preg_match_all('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s', $text, $matches)) {
            $certs = $matches[1];
        } else {
            $certs = [$text];
        }
        $result = [];
        foreach ($certs as $cert) {
            $cert = preg_replace('/\s+/', '', $cert);
            if ($cert !== '') {
                $result[] = $cert;
            }
        }
        return $result;
    }

    /**
     * Wrap bare base64 in PEM armour.
     *
     * @param string $cert
     * @return string
     */
    public static function to_pem(string $cert): string {
        return "-----BEGIN CERTIFICATE-----\n" . chunk_split($cert, 64, "\n") . "-----END CERTIFICATE-----\n";
    }

    /**
     * Copy parsed metadata into the IDP record and certjson.
     *
     * @param stdClass $idp record or new data
     * @param array $metadata see saml::parse_idp_metadata()
     * @return stdClass modified record, not stored
     */
    public static function apply_metadata(stdClass $idp, array $metadata): stdClass {
        $idp->entityid = $metadata['entityid'];
        $idp->ssourl = $metadata['ssourl'];
        $idp->logouturl = $metadata['slourl'];

        $info = self::get_certinfo($idp);
        $info['certs'] = [];
        foreach ($metadata['certs'] as $cert) {
            $parsed = openssl::parse_cert(self::to_pem($cert));
            $info['certs'][] = [
                'cert' => $cert,
                'subject' => $parsed['subject'] ?? '',
                'notafter' => $parsed['notafter'] ?? null,
                'fingerprint' => $parsed['fingerprint'] ?? null,
            ];
        }
        $info['nameidformat'] = $metadata['nameidformat'];
        $info['attributes'] = $metadata['attributes'];
        $info['validuntil'] = $metadata['validuntil'];
        $info['fetched'] = time();
        $info['attempted'] = time();
        $info['error'] = null;
        $idp->certjson = json_encode($info);

        return $idp;
    }

    /**
     * Create new IDP.
     *
     * @param stdClass $data form data plus 'metadata' (parsed) and optional 'attributes' (provider defaults)
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $record = new stdClass();
        $record->provider = self::normalise_provider($data->provider ?? null);
        $record->metadataurl = trim((string)$data->metadataurl);
        $record->name = trim((string)$data->name);
        $record->enabled = (int)(bool)($data->enabled ?? 0);
        $record->customsettingsjson = self::normalise_json($data->customsettingsjson ?? null);
        $record->attrsimple = (int)(bool)($data->attrsimple ?? 0);
        $record->mapattr = trim((string)($data->mapattr ?? provider::NAMEID_ATTRIBUTE));
        $record->autologin = (int)(bool)($data->autologin ?? 0);
        $record->autocreate = (int)(bool)($data->autocreate ?? 0);
        $record->automap = (int)(bool)($data->automap ?? 0);
        $record->usernameprefix = \core_text::strtolower(trim((string)($data->usernameprefix ?? '')));
        $record->tenantid = empty($data->tenantid) ? null : (int)$data->tenantid;
        $record->certjson = null;

        if ($record->name === '' || $record->mapattr === '') {
            throw new coding_exception('name and mapattr are required');
        }
        $record = self::apply_metadata($record, $data->metadata);
        if (self::fetch_by_entityid($record->entityid)) {
            throw new moodle_exception('error_entityidexists', 'auth_musaml', '', $record->entityid);
        }

        $trans = $DB->start_delegated_transaction();
        $record->id = $DB->insert_record('auth_musaml_idp', $record);
        if (!empty($data->attributes)) {
            attribute::create_from_defaults($record->id, $data->attributes);
        }
        $trans->allow_commit();

        $idp = self::fetch($record->id);
        \auth_musaml\event\idp_created::create_from_idp($idp)->trigger();

        return $idp;
    }

    /**
     * Update IDP.
     *
     * @param stdClass $data form data with id, optional 'metadata' when the URL changed
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $record = self::fetch($data->id);
        if (!$record) {
            throw new coding_exception('invalid idp id');
        }
        foreach (['provider', 'metadataurl', 'name', 'mapattr', 'usernameprefix'] as $field) {
            if (isset($data->$field)) {
                $record->$field = trim((string)$data->$field);
            }
        }
        $record->provider = self::normalise_provider($record->provider);
        $record->usernameprefix = \core_text::strtolower($record->usernameprefix);
        foreach (['enabled', 'attrsimple', 'autologin', 'autocreate', 'automap'] as $field) {
            if (isset($data->$field)) {
                $record->$field = (int)(bool)$data->$field;
            }
        }
        if (property_exists($data, 'customsettingsjson')) {
            $record->customsettingsjson = self::normalise_json($data->customsettingsjson);
        }
        if (property_exists($data, 'tenantid')) {
            $record->tenantid = empty($data->tenantid) ? null : (int)$data->tenantid;
        }
        if (!empty($data->metadata)) {
            $record = self::apply_metadata($record, $data->metadata);
            $other = self::fetch_by_entityid($record->entityid);
            if ($other && $other->id != $record->id) {
                throw new moodle_exception('error_entityidexists', 'auth_musaml', '', $record->entityid);
            }
        }
        if ($record->name === '' || $record->mapattr === '') {
            throw new coding_exception('name and mapattr are required');
        }

        $DB->update_record('auth_musaml_idp', $record);

        $idp = self::fetch($record->id);
        \auth_musaml\event\idp_updated::create_from_idp($idp)->trigger();

        return $idp;
    }

    /**
     * Delete IDP with its attribute mappings and user mappings.
     *
     * Users keep their accounts, they just cannot log in through this IDP any more.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB;

        $idp = self::fetch($id);
        if (!$idp) {
            return;
        }

        $trans = $DB->start_delegated_transaction();
        attribute::delete_for_idp($id);
        mapping::delete_for_idp($id);
        assertion::delete_for_idp($id);
        login::delete_for_idp($id);
        $DB->delete_records('auth_musaml_idp', ['id' => $id]);
        $trans->allow_commit();

        \auth_musaml\event\idp_deleted::create_from_idp($idp)->trigger();
    }

    /**
     * Is opportunistic metadata refresh due?
     *
     * Only the age of the last fetch matters, metadata validUntil is ignored because
     * some IDPs publish documents valid for minutes while their certificates last years.
     *
     * @param stdClass $idp
     * @return bool
     */
    public static function needs_refresh(stdClass $idp): bool {
        // Metadata pasted as XML has nowhere to refresh from.
        if (trim((string)$idp->metadataurl) === '') {
            return false;
        }
        $info = self::get_certinfo($idp);
        if (!$info['autorefresh']) {
            return false;
        }
        $now = time();
        if ($info['attempted'] && $info['attempted'] > $now - self::REFRESH_RETRY) {
            return false;
        }
        return !$info['fetched'] || $info['fetched'] < $now - self::REFRESH_INTERVAL;
    }

    /**
     * Do the stored certificates differ from the given metadata?
     *
     * @param stdClass $idp
     * @param array $metadata
     * @return bool
     */
    public static function certificates_changed(stdClass $idp, array $metadata): bool {
        $info = self::get_certinfo($idp);
        $stored = array_map(fn($c) => $c['cert'], $info['certs']);
        sort($stored);
        $new = $metadata['certs'];
        sort($new);
        return $stored !== $new;
    }

    /**
     * Download metadata again and store the result.
     *
     * Failures are recorded in certjson and never thrown, the previous certificates stay in use.
     *
     * @param stdClass $idp
     * @return string|null error message, null on success
     */
    public static function refresh_metadata(stdClass $idp): ?string {
        global $DB;

        if (trim((string)$idp->metadataurl) === '') {
            // The metadata was pasted as XML, there is nothing to fetch.
            return get_string('error_metadatanourl', 'auth_musaml');
        }

        try {
            $metadata = self::load_metadata($idp->metadataurl);
            $other = self::fetch_by_entityid($metadata['entityid']);
            if ($other && $other->id != $idp->id) {
                throw new moodle_exception('error_entityidexists', 'auth_musaml', '', $metadata['entityid']);
            }
            $idp = self::apply_metadata($idp, $metadata);
            $DB->update_record('auth_musaml_idp', $idp);
            return null;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $info = self::get_certinfo($idp);
            $info['attempted'] = time();
            $info['error'] = $error;
            $idp->certjson = json_encode($info);
            $DB->set_field('auth_musaml_idp', 'certjson', $idp->certjson, ['id' => $idp->id]);
            saml::log('metadata refresh failed for IDP ' . $idp->id . ': ' . $error);

            \auth_musaml\event\metadata_refresh_failed::create_from_idp($idp, $error)->trigger();

            return $error;
        }
    }

    /**
     * IDP block of php-saml settings.
     *
     * @param stdClass $idp
     * @return array
     */
    public static function get_idp_settings(stdClass $idp): array {
        $info = self::get_certinfo($idp);
        // Certificates added by hand are trusted next to the ones from metadata,
        // they cover key rollover that the identity provider has not published yet.
        $all = array_merge($info['certs'], $info['extracerts']);
        $certs = array_values(array_unique(array_map(fn($c) => $c['cert'], $all)));

        $settings = [
            'entityId' => $idp->entityid,
            'singleSignOnService' => [
                'url' => $idp->ssourl,
                'binding' => \OneLogin\Saml2\Constants::BINDING_HTTP_REDIRECT,
            ],
            'x509certMulti' => [
                'signing' => $certs,
                'encryption' => $certs,
            ],
        ];
        if ($idp->logouturl) {
            $settings['singleLogoutService'] = [
                'url' => $idp->logouturl,
                'binding' => \OneLogin\Saml2\Constants::BINDING_HTTP_REDIRECT,
            ];
        }
        return $settings;
    }

    /**
     * Warning shown when automatic login is used with a product that cannot log out centrally.
     *
     * @param stdClass $idp
     * @return string|null
     */
    public static function get_autologin_warning(stdClass $idp): ?string {
        if (!$idp->autologin) {
            return null;
        }
        $providerclass = provider::get_class($idp->provider);
        if ($providerclass::supports_slo()) {
            return null;
        }
        return get_string('idp_autologin_noslo', 'auth_musaml', $providerclass::get_name());
    }

    /**
     * Is the tenant archived or gone?
     *
     * @param int $tenantid
     * @return bool false when multi-tenancy is not active
     */
    public static function is_tenant_archived(int $tenantid): bool {
        global $DB;

        if (!\tool_mulib\local\mulib::is_mutenancy_active()) {
            return false;
        }
        return !$DB->record_exists('tool_mutenancy_tenant', ['id' => $tenantid, 'archived' => 0]);
    }

    /**
     * Tenant menu for forms, empty if multi-tenancy is not active.
     *
     * @return array
     */
    public static function get_tenant_menu(): array {
        global $DB;
        if (!\tool_mulib\local\mulib::is_mutenancy_active()) {
            return [];
        }
        $menu = [0 => get_string('tenant_all', 'auth_musaml')];
        $tenants = $DB->get_records_menu('tool_mutenancy_tenant', ['archived' => 0], 'name ASC', 'id, name');
        foreach ($tenants as $id => $name) {
            $menu[$id] = format_string($name);
        }
        return $menu;
    }

    /**
     * Tenant name for display.
     *
     * @param stdClass $idp
     * @return string
     */
    public static function get_tenant_name(stdClass $idp): string {
        global $DB;
        if (!$idp->tenantid) {
            return get_string('tenant_all', 'auth_musaml');
        }
        $name = $DB->get_field('tool_mutenancy_tenant', 'name', ['id' => $idp->tenantid]);
        if ($name === false) {
            return get_string('unknown', 'core');
        }
        return format_string($name);
    }

    /**
     * Set up an IDP management page with tabs.
     *
     * @param stdClass $idp
     * @param url $pageurl
     * @param string $activetab
     */
    public static function setup_page(stdClass $idp, url $pageurl, string $activetab): void {
        global $PAGE, $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        admin_externalpage_setup('auth_musaml_idps', '', ['id' => $idp->id], $pageurl, ['nosearch' => true]);

        $PAGE->set_heading(format_string($idp->name));
        $PAGE->navbar->add(format_string($idp->name), $pageurl);

        $secondarynav = new \auth_musaml\navigation\views\idp_secondary($PAGE, $idp);
        $PAGE->set_secondarynav($secondarynav);
        $PAGE->set_secondary_active_tab($activetab);
        $secondarynav->initialise();
    }

    /**
     * Set up a user mapping page.
     *
     * Mapping managers do not need to administer the site, so the page falls back to a
     * plain admin layout for them, without links to pages they cannot open.
     *
     * @param stdClass $idp
     * @param url $pageurl
     */
    public static function setup_mappings_page(stdClass $idp, url $pageurl): void {
        global $PAGE;

        if (has_capability('moodle/site:config', \core\context\system::instance())) {
            self::setup_page($idp, $pageurl, 'idp_mappings');
            return;
        }

        $PAGE->set_context(\core\context\system::instance());
        $PAGE->set_url($pageurl);
        $PAGE->set_pagelayout('admin');
        $PAGE->set_title(get_string('user_mappings', 'auth_musaml'));
        $PAGE->set_heading(format_string($idp->name));
        $PAGE->set_secondary_navigation(false);
        $PAGE->navbar->add(get_string('user_mappings', 'auth_musaml'), $pageurl);
    }

    /**
     * Valid provider type or generic.
     *
     * @param string|null $provider
     * @return string
     */
    private static function normalise_provider(?string $provider): string {
        $all = provider::get_all();
        return isset($all[$provider ?? '']) ? $provider : 'generic';
    }

    /**
     * Empty JSON becomes null.
     *
     * @param string|null $json
     * @return string|null
     */
    private static function normalise_json(?string $json): ?string {
        $json = trim((string)$json);
        return $json === '' ? null : $json;
    }
}
