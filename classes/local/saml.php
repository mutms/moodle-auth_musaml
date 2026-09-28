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

use core\exception\moodle_exception;
use core\url;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\IdPMetadataParser;
use OneLogin\Saml2\Settings;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use stdClass;

/**
 * Glue between Moodle configuration and the php-saml library.
 *
 * Service provider certificate, key and passphrase are stored in config_plugins,
 * the passphrase is encrypted with the site encryption key.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class saml {
    /** @var string plugin config name of SP certificate PEM */
    public const CONFIG_SP_CERT = 'sp_cert';

    /** @var string plugin config name of encrypted SP private key PEM */
    public const CONFIG_SP_KEY = 'sp_key';

    /** @var string plugin config name of encrypted SP key passphrase */
    public const CONFIG_SP_KEYPASS = 'sp_keypass';

    /**
     * Load the vendored php-saml library.
     *
     * The Composer autoloader of the plugin is deliberately not used, see vendor/README.md.
     */
    public static function init(): void {
        \tool_mulib\local\vendor_loader::register(__DIR__ . '/../../vendor');
    }

    /**
     * Base URL of all SAML endpoints, used by php-saml to build and verify URLs.
     *
     * @return string
     */
    public static function get_base_url(): string {
        global $CFG;
        return $CFG->wwwroot . '/auth/musaml/endpoints/';
    }

    /**
     * URL of one SP endpoint.
     *
     * @param string $endpoint acs, sls or metadata
     * @return url
     */
    public static function get_endpoint_url(string $endpoint): url {
        return new url('/auth/musaml/endpoints/' . $endpoint . '.php');
    }

    /**
     * SP metadata URL.
     *
     * Metadata is a published document, not a SAML endpoint, and it sits at the well known
     * location the entity ID points to.
     *
     * @return url
     */
    public static function get_metadata_url(): url {
        return new url('/auth/musaml/metadata.php');
    }

    /**
     * SP entity ID, falls back to the metadata URL.
     *
     * @return string
     */
    public static function get_sp_entityid(): string {
        $entityid = trim((string)get_config('auth_musaml', 'sp_entityid'));
        if ($entityid !== '') {
            return $entityid;
        }
        return self::get_metadata_url()->out(false);
    }

    /**
     * Does the site run on https?
     *
     * The identity provider posts the response from its own site, so the cookie that
     * carries the pending login needs SameSite=None, which browsers only accept on a
     * secure cookie. Identity providers refuse plain http endpoints as well.
     *
     * @return bool
     */
    public static function is_site_secure(): bool {
        return is_https();
    }

    /**
     * Is debug logging enabled?
     *
     * @return bool
     */
    public static function is_debug(): bool {
        return (bool)get_config('auth_musaml', 'debug');
    }

    /**
     * Write a debug message to the PHP error log when debugging is enabled.
     *
     * @param string $message
     */
    public static function log(string $message): void {
        if (!self::is_debug()) {
            return;
        }
        // Debug output is intended for the web server log, not the page.
        error_log('auth_musaml: ' . $message); // phpcs:ignore
    }

    /**
     * Is SP certificate and key available?
     *
     * @return bool
     */
    public static function has_sp_certificate(): bool {
        $cert = get_config('auth_musaml', self::CONFIG_SP_CERT);
        $key = get_config('auth_musaml', self::CONFIG_SP_KEY);
        $pass = get_config('auth_musaml', self::CONFIG_SP_KEYPASS);
        return !empty($cert) && !empty($key) && !empty($pass);
    }

    /**
     * SP certificate in PEM format.
     *
     * @return string|null
     */
    public static function get_sp_certificate(): ?string {
        $cert = get_config('auth_musaml', self::CONFIG_SP_CERT);
        return $cert ? $cert : null;
    }

    /**
     * Parsed SP certificate details.
     *
     * @return array|null
     */
    public static function get_sp_certificate_info(): ?array {
        $cert = self::get_sp_certificate();
        if (!$cert) {
            return null;
        }
        return openssl::parse_cert($cert);
    }

    /**
     * Decrypted SP private key in PEM format.
     *
     * @return string|null
     */
    public static function get_sp_private_key(): ?string {
        $keypem = get_config('auth_musaml', self::CONFIG_SP_KEY);
        $encryptedpass = get_config('auth_musaml', self::CONFIG_SP_KEYPASS);
        if (!$keypem || !$encryptedpass) {
            return null;
        }
        try {
            $pass = \core\encryption::decrypt($encryptedpass);
        } catch (moodle_exception $e) {
            debugging('Cannot decrypt SP key passphrase: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
        $key = openssl::load_key($keypem, $pass);
        if (!$key) {
            debugging('Cannot load SP private key', DEBUG_DEVELOPER);
            return null;
        }
        return openssl::export_key_plain($key);
    }

    /**
     * Default certificate subject built from the site.
     *
     * @return array
     */
    public static function get_default_dn(): array {
        global $CFG, $SITE;

        $host = parse_url($CFG->wwwroot, PHP_URL_HOST) ?: 'moodle';
        $org = trim((string)$SITE->shortname);
        if ($org === '') {
            $org = 'Moodle';
        }
        return [
            'commonName' => $host,
            'organizationName' => $org,
        ];
    }

    /**
     * Create and store a new SP certificate, private key and passphrase.
     *
     * Old values are replaced, all IDPs must receive the new SP metadata.
     *
     * @param array|null $dn certificate subject, defaults to site values
     * @param int $days certificate validity
     */
    public static function regenerate_sp_certificate(?array $dn = null, int $days = openssl::CERT_DAYS): void {
        $dn = $dn ?? self::get_default_dn();
        $key = openssl::generate_key();
        $cert = openssl::create_self_signed_cert($key, $dn, $days);
        $pass = random_string(40);
        $keypem = openssl::export_key($key, $pass);

        set_config(self::CONFIG_SP_CERT, $cert, 'auth_musaml');
        set_config(self::CONFIG_SP_KEY, $keypem, 'auth_musaml');
        set_config(self::CONFIG_SP_KEYPASS, \core\encryption::encrypt($pass), 'auth_musaml');
    }

    /**
     * Remove SP certificate, key and passphrase.
     */
    public static function delete_sp_certificate(): void {
        unset_config(self::CONFIG_SP_CERT, 'auth_musaml');
        unset_config(self::CONFIG_SP_KEY, 'auth_musaml');
        unset_config(self::CONFIG_SP_KEYPASS, 'auth_musaml');
    }

    /**
     * SP part of php-saml settings.
     *
     * @return array
     */
    public static function get_sp_settings(): array {
        self::init();

        $sp = [
            'entityId' => self::get_sp_entityid(),
            'assertionConsumerService' => [
                'url' => self::get_endpoint_url('acs')->out(false),
                'binding' => Constants::BINDING_HTTP_POST,
            ],
            'singleLogoutService' => [
                'url' => self::get_endpoint_url('sls')->out(false),
                'binding' => Constants::BINDING_HTTP_REDIRECT,
            ],
            'NameIDFormat' => Constants::NAMEID_UNSPECIFIED,
            'x509cert' => (string)self::get_sp_certificate(),
            'privateKey' => (string)self::get_sp_private_key(),
        ];

        return $sp;
    }

    /**
     * Security defaults, any of these may be overridden in IDP custom settings.
     *
     * @return array
     */
    public static function get_default_security_settings(): array {
        self::init();

        return [
            'nameIdEncrypted' => false,
            'authnRequestsSigned' => true,
            'logoutRequestSigned' => true,
            'logoutResponseSigned' => true,
            'signMetadata' => true,
            'wantMessagesSigned' => false,
            'wantAssertionsSigned' => true,
            'wantAssertionsEncrypted' => false,
            'wantNameId' => true,
            'wantNameIdEncrypted' => false,
            'wantXMLValidation' => true,
            'requestedAuthnContext' => false,
            'rejectUnsolicitedResponsesWithInResponseTo' => true,
            'relaxDestinationValidation' => false,
            'destinationStrictlyMatches' => true,
            'requireDestination' => true,
            'allowRepeatAttributeName' => false,
            'signatureAlgorithm' => XMLSecurityKey::RSA_SHA256,
            'digestAlgorithm' => XMLSecurityDSig::SHA256,
            'encryption_algorithm' => XMLSecurityKey::AES128_GCM,
            'lowercaseUrlencoding' => false,
        ];
    }

    /**
     * Top level defaults without any IDP data.
     *
     * @return array
     */
    public static function get_default_settings(): array {
        return [
            'strict' => true,
            'debug' => false,
            'baseurl' => self::get_base_url(),
            'sp' => self::get_sp_settings(),
            'security' => self::get_default_security_settings(),
            'contactPerson' => self::get_contact_settings(),
        ];
    }

    /**
     * Apply IDP custom settings JSON over the defaults.
     *
     * This is an escape hatch, anything may be overridden including unsafe options,
     * use get_unsafe_overrides() to warn admins.
     *
     * @param array $settings
     * @param string|null $json
     * @return array
     */
    public static function apply_custom_settings(array $settings, ?string $json): array {
        $custom = self::decode_custom_settings($json);
        if (!$custom) {
            return $settings;
        }
        return array_replace_recursive($settings, $custom);
    }

    /**
     * Decode custom settings JSON.
     *
     * @param string|null $json
     * @return array|null null if empty or invalid
     */
    public static function decode_custom_settings(?string $json): ?array {
        $json = trim((string)$json);
        if ($json === '') {
            return null;
        }
        $custom = json_decode($json, true);
        if (!is_array($custom)) {
            return null;
        }
        return $custom;
    }

    /**
     * Validate custom settings JSON.
     *
     * @param string|null $json
     * @return string|null error message or null if valid
     */
    public static function validate_custom_settings(?string $json): ?string {
        $json = trim((string)$json);
        if ($json === '') {
            return null;
        }
        $custom = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return get_string('error_customsettings_json', 'auth_musaml', json_last_error_msg());
        }
        if (!is_array($custom) || ($custom && array_is_list($custom))) {
            return get_string('error_customsettings_object', 'auth_musaml');
        }
        return null;
    }

    /**
     * List of overridden options that weaken security compared to defaults.
     *
     * @param array $settings merged settings
     * @return array human readable "key = value" strings
     */
    public static function get_unsafe_overrides(array $settings): array {
        $unsafe = [];

        $toplevel = ['strict' => true, 'debug' => false];
        foreach ($toplevel as $key => $safe) {
            if (array_key_exists($key, $settings) && $settings[$key] !== $safe) {
                $unsafe[] = $key . ' = ' . json_encode($settings[$key]);
            }
        }

        $security = $settings['security'] ?? [];
        $safebools = [
            'wantAssertionsSigned' => true,
            'wantNameId' => true,
            'wantXMLValidation' => true,
            'rejectUnsolicitedResponsesWithInResponseTo' => true,
            'destinationStrictlyMatches' => true,
            'requireDestination' => true,
            'relaxDestinationValidation' => false,
            'allowRepeatAttributeName' => false,
        ];
        foreach ($safebools as $key => $safe) {
            if (array_key_exists($key, $security) && $security[$key] !== $safe) {
                $unsafe[] = 'security.' . $key . ' = ' . json_encode($security[$key]);
            }
        }

        foreach (['signatureAlgorithm', 'digestAlgorithm'] as $key) {
            $value = $security[$key] ?? '';
            if (is_string($value) && preg_match('/sha1|md5/i', $value)) {
                $unsafe[] = 'security.' . $key . ' = ' . json_encode($value);
            }
        }
        $value = $security['encryption_algorithm'] ?? '';
        if (is_string($value) && preg_match('/tripledes|3des|aes128-cbc|aes192-cbc|aes256-cbc/i', $value)) {
            $unsafe[] = 'security.encryption_algorithm = ' . json_encode($value);
        }

        // Fingerprints replace the pinned certificates with a SHA-1 comparison of whatever the IDP sends.
        if (!empty($settings['idp']['certFingerprint'])) {
            $unsafe[] = 'idp.certFingerprint = ' . json_encode($settings['idp']['certFingerprint']);
        }

        return $unsafe;
    }

    /**
     * Contacts for SP metadata, taken from the site support contact settings.
     *
     * @return array
     */
    public static function get_contact_settings(): array {
        global $CFG, $SITE;

        $email = trim((string)($CFG->supportemail ?? ''));
        if ($email === '' || !validate_email($email)) {
            return [];
        }
        $name = trim((string)($CFG->supportname ?? ''));
        if ($name === '') {
            $name = format_string($SITE->fullname);
        }
        $contact = ['givenName' => $name, 'emailAddress' => $email];
        return ['technical' => $contact, 'support' => $contact];
    }

    /**
     * Parse IDP metadata XML.
     *
     * @param string $xml
     * @return array keys: entityid, ssourl, slourl, certs (list of base64 without PEM armour),
     *               nameidformat, attributes (advertised attribute names), validuntil, displayname
     * @throws moodle_exception if the XML is not usable IDP metadata
     */
    public static function parse_idp_metadata(string $xml): array {
        self::init();

        $xml = trim($xml);
        if ($xml === '') {
            throw new moodle_exception('error_metadataparse', 'auth_musaml', '', 'empty document');
        }
        // Broken XML is reported by the exception below, libxml must not print warnings into the page.
        $previous = libxml_use_internal_errors(true);
        try {
            $parsed = IdPMetadataParser::parseXML($xml);
        } catch (\Throwable $e) {
            // The library only says it failed, the first libxml error says why.
            $xmlerror = libxml_get_errors()[0] ?? null;
            $detail = $xmlerror ? trim($xmlerror->message) : $e->getMessage();
            throw new moodle_exception('error_metadataparse', 'auth_musaml', '', $detail);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $idp = $parsed['idp'] ?? [];
        if (empty($idp['entityId']) || empty($idp['singleSignOnService']['url'])) {
            throw new moodle_exception('error_metadataparse', 'auth_musaml', '', 'no IDPSSODescriptor found');
        }
        if (\core_text::strlen($idp['entityId']) > 255) {
            throw new moodle_exception('error_entityidtoolong', 'auth_musaml');
        }

        $certs = [];
        if (!empty($idp['x509cert'])) {
            $certs[] = openssl::strip_pem($idp['x509cert']);
        }
        foreach ($idp['x509certMulti']['signing'] ?? [] as $cert) {
            $certs[] = openssl::strip_pem($cert);
        }
        $certs = array_values(array_unique($certs));
        if (!$certs) {
            throw new moodle_exception('error_metadataparse', 'auth_musaml', '', 'no signing certificate found');
        }

        $result = [
            'entityid' => $idp['entityId'],
            'ssourl' => $idp['singleSignOnService']['url'],
            'slourl' => $idp['singleLogoutService']['url'] ?? null,
            'certs' => $certs,
            'nameidformat' => $parsed['sp']['NameIDFormat'] ?? null,
            'attributes' => [],
            'validuntil' => null,
            'displayname' => null,
        ];

        // Details php-saml does not extract.
        $dom = new \DOMDocument();
        $dom->loadXML($xml, LIBXML_NONET);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('md', Constants::NS_MD);
        $xpath->registerNamespace('saml', Constants::NS_SAML);
        $xpath->registerNamespace('mdui', 'urn:oasis:names:tc:SAML:metadata:ui');

        foreach ($xpath->query('//md:IDPSSODescriptor/saml:Attribute/@Name') as $attr) {
            $result['attributes'][] = $attr->value;
        }
        foreach ($xpath->query('/md:EntityDescriptor/@validUntil | //md:IDPSSODescriptor/@validUntil') as $attr) {
            $time = strtotime($attr->value);
            if ($time && (!$result['validuntil'] || $time < $result['validuntil'])) {
                $result['validuntil'] = $time;
            }
        }
        $names = $xpath->query('//md:IDPSSODescriptor//mdui:DisplayName');
        if ($names->length) {
            $result['displayname'] = trim($names->item(0)->textContent) ?: null;
        }

        return $result;
    }

    /**
     * Complete php-saml settings for one IDP.
     *
     * Order: plugin defaults, IDP data, provider adjustments, admin JSON override.
     *
     * @param stdClass $idp
     * @return array
     */
    public static function build_settings(stdClass $idp): array {
        $settings = self::get_default_settings();
        $settings['idp'] = idp::get_idp_settings($idp);

        $provider = provider\base::get_class($idp->provider);
        $settings = $provider::adjust_settings($settings, $idp);

        return self::apply_custom_settings($settings, $idp->customsettingsjson);
    }

    /**
     * php-saml Auth instance for one IDP.
     *
     * @param stdClass $idp
     * @return Auth
     */
    public static function create_auth(stdClass $idp): Auth {
        self::init();
        return new Auth(self::build_settings($idp));
    }

    /**
     * Signed SP metadata XML, null before the SP certificate is created.
     *
     * @return string|null
     * @throws moodle_exception if the metadata is invalid
     */
    public static function get_sp_metadata(): ?string {
        if (!self::has_sp_certificate()) {
            return null;
        }
        self::init();

        $settings = new Settings(self::get_default_settings(), true);
        $metadata = $settings->getSPMetadata(true, null, null, true);
        $errors = $settings->validateMetadata($metadata);
        if ($errors) {
            throw new moodle_exception('error_spmetadata', 'auth_musaml', '', implode(', ', $errors));
        }
        return $metadata;
    }
}
