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
use core\exception\moodle_exception;
use core\url;
use stdClass;

/**
 * SAML login flow helper.
 *
 * The pending authentication request is kept in the Moodle session so the
 * response can be tied to the request that started it.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class login {
    /** @var int pending request expires after this many seconds */
    public const REQUEST_TTL = 600;

    /** @var string cookie name holding the token of a login in progress */
    public const REQUEST_COOKIE = 'musaml_login';

    /** @var string login page parameter that turns automatic SSO redirect off */
    public const PARAM_AUTOLOGIN = 'musaml';

    /** @var string value of the parameter above */
    public const AUTOLOGIN_OFF = 'off';

    /** @var string cookie counting automatic redirects, it survives a lost session */
    public const AUTOLOGIN_COOKIE = 'musaml_auto';

    /** @var int automatic redirects allowed inside the window below */
    public const AUTOLOGIN_LIMIT = 3;

    /** @var int length of the rate limit window in seconds */
    public const AUTOLOGIN_WINDOW = 120;

    /**
     * Current tenant id if multi-tenancy is active.
     *
     * @return int|null
     */
    public static function get_current_tenantid(): ?int {
        if (!\tool_mulib\local\mulib::is_mutenancy_active()) {
            return null;
        }
        return \tool_mutenancy\local\tenancy::get_current_tenantid();
    }

    /**
     * May a response of this IDP still be accepted?
     *
     * Tenant filtering decides which identity providers a login page offers, it must not
     * be repeated here: the response arrives without a session and without the tenant
     * cookie, and the pending login already proves where the login started.
     *
     * @param stdClass $idp
     * @return bool
     */
    public static function is_idp_usable(stdClass $idp): bool {
        if (!$idp->enabled) {
            return false;
        }
        if ($idp->tenantid && idp::is_tenant_archived($idp->tenantid)) {
            // An archived tenant is being wound down, nobody signs in any more.
            return false;
        }
        return true;
    }

    /**
     * Can the IDP be used for login right now?
     *
     * @param stdClass $idp
     * @return bool
     */
    public static function is_idp_available(stdClass $idp): bool {
        if (!$idp->enabled) {
            return false;
        }
        if ($idp->tenantid) {
            if ($idp->tenantid != self::get_current_tenantid()) {
                return false;
            }
            // An archived tenant is being wound down, its login page is hidden too.
            if (idp::is_tenant_archived($idp->tenantid)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Login page URL for the IDP.
     *
     * @param stdClass $idp
     * @param string $wantsurl
     * @return url
     */
    public static function get_login_url(stdClass $idp, string $wantsurl = ''): url {
        $params = ['id' => $idp->id];
        if ($wantsurl !== '') {
            $params['wantsurl'] = $wantsurl;
        }
        return new url('/auth/musaml/login.php', $params);
    }

    /**
     * Start the login, returns the IDP redirect URL.
     *
     * @param stdClass $idp
     * @param string $wantsurl
     * @param bool $test true for the admin test login, nobody is logged in
     * @return string
     */
    public static function start(stdClass $idp, string $wantsurl = '', bool $test = false): string {
        global $DB, $CFG;

        $auth = saml::create_auth($idp);
        $redirecturl = $auth->login($CFG->wwwroot, [], false, false, true);

        // The response arrives as a cross site POST without the Moodle session cookie,
        // so the pending login lives in the database and our own cookie holds the key.
        self::cleanup_requests();
        $record = new stdClass();
        $record->idpid = $idp->id;
        $record->token = random_string(48);
        $record->requestid = $auth->getLastRequestID();
        $record->wantsurl = $wantsurl;
        $record->test = (int)$test;
        $record->ready = 0;
        $record->resultjson = null;
        $record->timecreated = time();
        $record->timeexpires = $record->timecreated + self::REQUEST_TTL;
        $DB->insert_record('auth_musaml_login', $record);
        self::set_request_cookie($record->token);

        saml::log('login started for IDP ' . $idp->id . ' request ' . $auth->getLastRequestID());

        return $redirecturl;
    }

    /**
     * Page that finishes a login, the response arrives without a session and redirects here.
     *
     * @param stdClass|null $request pending login, null when there is none
     * @return string
     */
    public static function get_result_url(?stdClass $request): string {
        if (!$request) {
            return self::get_retry_url();
        }
        if ($request->test) {
            return (new url('/auth/musaml/management/test_login.php'))->out(false);
        }
        return (new url('/auth/musaml/complete.php'))->out(false);
    }

    /**
     * Delete the pending logins of one IDP.
     *
     * @param int $idpid
     */
    public static function delete_for_idp(int $idpid): void {
        global $DB;
        $DB->delete_records('auth_musaml_login', ['idpid' => $idpid]);
    }

    /**
     * Pending login of this browser, null when there is none.
     *
     * @return stdClass|null
     */
    public static function fetch_request(): ?stdClass {
        global $DB;

        $token = $_COOKIE[self::REQUEST_COOKIE] ?? '';
        if (!is_string($token) || $token === '') {
            return null;
        }
        $record = $DB->get_record('auth_musaml_login', ['token' => $token]);
        if (!$record || $record->timeexpires < time()) {
            return null;
        }
        return $record;
    }

    /**
     * Store the outcome of a response and stop accepting further responses for it.
     *
     * @param stdClass $request
     * @param array $result attributes of an accepted response, or a failure reason
     * @param int|null $userid user the response resolved to, null when there is none
     */
    public static function store_result(stdClass $request, array $result, ?int $userid): void {
        global $DB;

        $DB->update_record('auth_musaml_login', (object)[
            'id' => $request->id,
            'ready' => 1,
            'userid' => $userid,
            'resultjson' => json_encode($result),
        ]);
    }

    /**
     * Take the finished login, it can be used once.
     *
     * @return array|null keys: request record, result array
     */
    public static function pop_result(): ?array {
        global $DB;

        $request = self::fetch_request();
        self::clear_request_cookie();
        if (!$request) {
            return null;
        }
        $DB->delete_records('auth_musaml_login', ['id' => $request->id]);
        if (!$request->ready) {
            return null;
        }
        $result = json_decode((string)$request->resultjson, true);
        if (!is_array($result)) {
            return null;
        }
        return ['request' => $request, 'result' => $result];
    }

    /**
     * Cookie of a login in progress.
     *
     * The response is posted by the identity provider, so the cookie must survive a cross
     * site POST, which needs SameSite=None and that needs Secure. Sites without https
     * only work with an identity provider of the same site, they are warned about it.
     *
     * @param string $token
     */
    private static function set_request_cookie(string $token): void {
        global $CFG;

        $secure = is_https();
        if (!CLI_SCRIPT && !headers_sent()) {
            setcookie(self::REQUEST_COOKIE, $token, [
                'expires' => time() + self::REQUEST_TTL,
                'path' => $CFG->sessioncookiepath ?? '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => $secure ? 'None' : 'Lax',
            ]);
        }
        $_COOKIE[self::REQUEST_COOKIE] = $token;
    }

    /**
     * Remove the cookie of a login in progress.
     */
    public static function clear_request_cookie(): void {
        global $CFG;

        $secure = is_https();
        if (!CLI_SCRIPT && !headers_sent()) {
            setcookie(self::REQUEST_COOKIE, '', [
                'expires' => time() - DAYSECS,
                'path' => $CFG->sessioncookiepath ?? '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => $secure ? 'None' : 'Lax',
            ]);
        }
        unset($_COOKIE[self::REQUEST_COOKIE]);
    }

    /**
     * Delete expired pending logins, nothing here depends on a scheduled task.
     */
    private static function cleanup_requests(): void {
        global $DB;
        $DB->delete_records_select('auth_musaml_login', 'timeexpires < :now', ['now' => time()]);
    }

    /**
     * Take the result of a test login, the row survives so the mapping form can use it.
     *
     * @return array|null keys: request record, result array
     */
    public static function take_test_result(): ?array {
        $request = self::fetch_request();
        self::clear_request_cookie();
        if (!$request || !$request->ready || !$request->test) {
            return null;
        }
        $result = json_decode((string)$request->resultjson, true);
        if (!is_array($result)) {
            return null;
        }
        return ['request' => $request, 'result' => $result];
    }

    /**
     * Point this administrator session at the login row of their last test.
     *
     * Only the row id is remembered, so two administrators testing the same identity
     * provider at the same time never see each other's values.
     *
     * @param int $idpid
     * @param int $loginid
     */
    public static function remember_test_result(int $idpid, int $loginid): void {
        global $SESSION;

        $tests = $SESSION->auth_musaml_test ?? [];
        $tests[$idpid] = $loginid;
        $SESSION->auth_musaml_test = $tests;
    }

    /**
     * Attributes of the last test login of this IDP, null when there is none any more.
     *
     * @param int $idpid
     * @return array|null keys: time, bag
     */
    public static function get_test_attributes(int $idpid): ?array {
        global $DB, $SESSION;

        $loginid = $SESSION->auth_musaml_test[$idpid] ?? null;
        if (!$loginid) {
            return null;
        }
        $record = $DB->get_record('auth_musaml_login', ['id' => $loginid, 'idpid' => $idpid, 'test' => 1]);
        if (!$record || $record->timeexpires < time()) {
            unset($SESSION->auth_musaml_test[$idpid]);
            return null;
        }
        $result = json_decode((string)$record->resultjson, true);
        if (!is_array($result) || !isset($result['bag'])) {
            return null;
        }
        return ['time' => $record->timecreated, 'bag' => $result['bag']];
    }


    /**
     * Validate the SAML response posted to the ACS endpoint.
     *
     * When validation fails and the IDP metadata can be refreshed, new certificates are
     * fetched and the same response is validated once more, this handles certificate
     * rotation without any admin action. The regular daily refresh runs after a
     * successful validation so users never wait for it before the SSO redirect.
     * Replayed assertions are rejected.
     *
     * @param stdClass $idp
     * @param string $requestid
     * @return \OneLogin\Saml2\Auth authenticated instance
     * @throws moodle_exception when the response is not valid
     */
    public static function process_response(stdClass $idp, string $requestid): \OneLogin\Saml2\Auth {
        $auth = saml::create_auth($idp);
        $reason = self::validate_response($auth, $requestid);

        if ($reason !== null && idp::get_certinfo($idp)['autorefresh']) {
            saml::log('response rejected for IDP ' . $idp->id . ', trying metadata refresh: ' . $reason);
            $before = idp::get_certinfo($idp)['certs'];
            if (idp::refresh_metadata($idp) === null) {
                $idp = idp::fetch($idp->id);
                if ($before !== idp::get_certinfo($idp)['certs']) {
                    $auth = saml::create_auth($idp);
                    $reason = self::validate_response($auth, $requestid);
                }
            }
        }
        if ($reason !== null) {
            saml::log('response rejected for IDP ' . $idp->id . ': ' . $reason);
            if (saml::is_debug()) {
                saml::log('response XML: ' . $auth->getLastResponseXML());
            }
            throw new moodle_exception('error_response', 'auth_musaml', '', $reason);
        }

        $assertionid = (string)$auth->getLastAssertionId();
        if (!assertion::register($idp->id, $assertionid, $auth->getLastAssertionNotOnOrAfter())) {
            saml::log('replayed assertion ' . $assertionid . ' for IDP ' . $idp->id);
            throw new moodle_exception('error_replay', 'auth_musaml');
        }

        if (idp::needs_refresh($idp)) {
            idp::refresh_metadata($idp);
        }

        return $auth;
    }

    /**
     * Run php-saml validation of the posted response.
     *
     * @param \OneLogin\Saml2\Auth $auth
     * @param string $requestid
     * @return string|null failure reason, null when valid
     */
    private static function validate_response(\OneLogin\Saml2\Auth $auth, string $requestid): ?string {
        try {
            $auth->processResponse($requestid);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
        if ($auth->getErrors()) {
            return $auth->getLastErrorReason() ?? implode(', ', $auth->getErrors());
        }
        if (!$auth->isAuthenticated()) {
            return 'not authenticated';
        }
        return null;
    }

    /**
     * Where a failed login sends the user to try again.
     *
     * Automatic SSO redirect is turned off, otherwise the login page would send the
     * user straight back to the identity provider that just rejected them.
     *
     * @return string
     */
    public static function get_retry_url(): string {
        $params = [self::PARAM_AUTOLOGIN => self::AUTOLOGIN_OFF];
        return (new url('/login/index.php', $params))->out(false);
    }

    /**
     * Have too many automatic redirects happened in a short time?
     *
     * A lost session or a rejected response can bounce the visitor between this site and
     * the identity provider, and the session flag cannot stop that because each round trip
     * starts a new session. The counter therefore lives in a cookie of its own.
     *
     * @return bool
     */
    public static function is_autologin_rate_limited(): bool {
        $counter = self::read_autologin_counter();
        return $counter['count'] >= self::AUTOLOGIN_LIMIT;
    }

    /**
     * Count one automatic redirect.
     */
    public static function count_autologin_redirect(): void {
        global $CFG;

        $counter = self::read_autologin_counter();
        $value = ($counter['count'] + 1) . ':' . $counter['since'];

        if (!CLI_SCRIPT && !headers_sent()) {
            setcookie(self::AUTOLOGIN_COOKIE, $value, [
                'expires' => time() + self::AUTOLOGIN_WINDOW,
                'path' => $CFG->sessioncookiepath ?? '/',
                'secure' => is_https(),
                'httponly' => true,
                'samesite' => 'None',
            ]);
        }
        $_COOKIE[self::AUTOLOGIN_COOKIE] = $value;
    }

    /**
     * Forget the automatic redirect counter.
     */
    public static function clear_autologin_counter(): void {
        global $CFG;

        if (!CLI_SCRIPT && !headers_sent()) {
            setcookie(self::AUTOLOGIN_COOKIE, '', [
                'expires' => time() - DAYSECS,
                'path' => $CFG->sessioncookiepath ?? '/',
            ]);
        }
        unset($_COOKIE[self::AUTOLOGIN_COOKIE]);
    }

    /**
     * Current redirect count and the start of its window.
     *
     * @return array keys count and since
     */
    private static function read_autologin_counter(): array {
        $raw = $_COOKIE[self::AUTOLOGIN_COOKIE] ?? '';
        if (!is_string($raw) || !preg_match('/^(\d+):(\d+)$/', $raw, $matches)) {
            return ['count' => 0, 'since' => time()];
        }
        $since = (int)$matches[2];
        if ($since < time() - self::AUTOLOGIN_WINDOW) {
            return ['count' => 0, 'since' => time()];
        }
        return ['count' => (int)$matches[1], 'since' => $since];
    }

    /**
     * Identity provider the login page should redirect to, if any.
     *
     * The redirect happens only when exactly one identity provider is available and it
     * asks for it, so nobody is ever sent to an identity provider that is not theirs.
     *
     * @return stdClass|null
     */
    public static function get_autologin_idp(): ?stdClass {
        if (!self::is_autologin_allowed()) {
            return null;
        }
        if (self::is_autologin_rate_limited()) {
            return null;
        }
        if (isloggedin() && !isguestuser()) {
            return null;
        }
        if (!saml::has_sp_certificate() || !is_enabled_auth('musaml')) {
            return null;
        }
        // Somebody is trying to log in with a password or another plugin.
        if (!empty($_POST)) {
            return null;
        }

        $idps = idp::get_enabled(self::get_current_tenantid());
        if (count($idps) !== 1) {
            return null;
        }
        $idp = reset($idps);
        if (!$idp->autologin || !self::is_idp_available($idp)) {
            return null;
        }
        return $idp;
    }

    /**
     * May the login page redirect to the identity provider on its own?
     *
     * Once turned off it stays off for the rest of the session, so the user can
     * use the login form or pick another identity provider.
     *
     * @return bool
     */
    public static function is_autologin_allowed(): bool {
        global $SESSION;

        if (optional_param(self::PARAM_AUTOLOGIN, '', PARAM_ALPHA) === self::AUTOLOGIN_OFF) {
            $SESSION->auth_musaml_noautologin = true;
        }
        return empty($SESSION->auth_musaml_noautologin);
    }

    /**
     * Decide which user a validated response belongs to.
     *
     * Runs where the response arrives, without a session, so it may create or map a user
     * but never logs anybody in. Every refusal ends in fail().
     *
     * @param stdClass $idp
     * @param array $bag attribute bag
     * @return stdClass user record
     * @throws moodle_exception when nobody may log in with this response
     */
    public static function authenticate(stdClass $idp, array $bag): stdClass {
        $guid = self::get_guid($bag, $idp);
        if ($guid === null) {
            self::fail($idp, 'error_noguid', $idp->mapattr);
        }

        $user = self::resolve_user($idp, $guid, $bag);
        if (!$user) {
            self::fail($idp, 'error_nomapping', $guid);
        }
        if ($user->deleted) {
            self::fail($idp, 'error_nomapping', $guid, $user->username);
        }
        if ($user->suspended) {
            self::fail($idp, 'error_suspended', null, $user->username);
        }
        if ($user->auth !== 'musaml') {
            // The nologin plugin is always enabled and exists to stop logins, honour that.
            $mapping = mapping::fetch_by_guid($idp->id, $guid);
            $usable = $user->auth !== 'nologin' && is_enabled_auth($user->auth);
            if (!$mapping || !$mapping->allowotherauth || !$usable) {
                self::fail($idp, 'error_otherauth', $user->auth, $user->username);
            }
        }

        return $user;
    }

    /**
     * Log the user in, this needs a session so it runs after the redirect.
     *
     * @param stdClass $idp
     * @param stdClass $user
     * @param array $bag attribute bag
     * @param array $samlsession keys nameid, nameidformat, sessionindex
     * @param string $wantsurl
     * @return url where to send the user next
     */
    public static function finish(
        stdClass $idp,
        stdClass $user,
        array $bag,
        array $samlsession,
        string $wantsurl
    ): url {
        global $SESSION, $CFG, $USER;

        if ($user->auth === 'musaml') {
            // Users kept on another authentication method are owned locally, their fields
            // are not locked either, so the identity provider does not overwrite them.
            attribute::sync_user($idp, $user, $bag, attribute::SYNC_ONLOGIN);
        }

        require_once($CFG->dirroot . '/login/lib.php');

        if (isloggedin() && $USER->id != $user->id) {
            // Somebody else is logged in here, drop their session data instead of inheriting it.
            require_logout();
        }

        $user = get_complete_user_data('id', $user->id, null, true);
        complete_user_login($user, ['auth_musaml_idpid' => $idp->id]);
        unset($SESSION->auth_musaml_noautologin);
        self::clear_autologin_counter();
        $SESSION->auth_musaml_session = [
            'idpid' => $idp->id,
            'nameid' => $samlsession['nameid'] ?? null,
            'nameidformat' => $samlsession['nameidformat'] ?? null,
            'sessionindex' => $samlsession['sessionindex'] ?? null,
        ];
        saml::log('user ' . $user->id . ' logged in via IDP ' . $idp->id);

        if ($wantsurl !== '') {
            // PARAM_LOCALURL keeps absolute URLs of this site, only relative ones need the prefix.
            $SESSION->wantsurl = preg_match('#^https?://#i', $wantsurl) ? $wantsurl : $CFG->wwwroot . $wantsurl;
        }
        return new url(core_login_get_return_url());
    }

    /**
     * User for the identity provider account, null when there is none.
     *
     * An existing mapping wins. Without one the IDP may map an existing user
     * automatically or create a new one, both are optional and deliberately strict.
     *
     * @param stdClass $idp
     * @param string $guid
     * @param array $bag
     * @return stdClass|null
     */
    public static function resolve_user(stdClass $idp, string $guid, array $bag): ?stdClass {
        global $DB;

        $mapping = mapping::fetch_by_guid($idp->id, $guid);
        if ($mapping) {
            $user = $DB->get_record('user', ['id' => $mapping->userid]);
            return $user ?: null;
        }

        if ($idp->automap) {
            $user = self::find_automap_user($idp, $bag);
            if ($user) {
                mapping::create((object)[
                    'idpid' => $idp->id,
                    'userid' => $user->id,
                    'guid' => $guid,
                    'automapped' => 1,
                ]);
                saml::log('user ' . $user->id . ' mapped automatically to ' . $guid . ' of IDP ' . $idp->id);
                return $user;
            }
        }

        if ($idp->autocreate) {
            $user = self::create_user($idp, $guid, $bag);
            saml::log('user ' . $user->id . ' created for ' . $guid . ' of IDP ' . $idp->id);
            return $user;
        }

        return null;
    }

    /**
     * Existing user matching all attributes flagged for automatic mapping.
     *
     * Only unmapped users using this plugin are considered and the match must be
     * unambiguous, anything else is a login failure the admin has to resolve.
     *
     * @param stdClass $idp
     * @param array $bag
     * @return stdClass|null
     */
    public static function find_automap_user(stdClass $idp, array $bag): ?stdClass {
        global $DB;

        $attributes = attribute::get_usermapping_for_idp($idp->id);
        if (!$attributes) {
            return null;
        }

        $wheres = [
            'u.deleted = 0',
            'u.auth = :auth',
            'NOT EXISTS (SELECT 1 FROM {auth_musaml_user} m WHERE m.userid = u.id)',
        ];
        $params = ['auth' => 'musaml'];
        $joins = '';
        $i = 0;

        foreach ($attributes as $attribute) {
            $value = attribute::get_value($attribute, $bag, $idp);
            if ($value === null) {
                // A missing value cannot identify anybody.
                return null;
            }
            $i++;
            if (userfield::is_profile_field($attribute->userfield)) {
                $shortname = userfield::get_profile_shortname($attribute->userfield);
                $joins .= " JOIN {user_info_data} d$i ON d$i.userid = u.id
                            JOIN {user_info_field} f$i ON f$i.id = d$i.fieldid AND f$i.shortname = :shortname$i ";
                $wheres[] = $DB->sql_equal("d$i.data", ":value$i", false);
                $params["shortname$i"] = $shortname;
                $params["value$i"] = $value;
            } else {
                $wheres[] = $DB->sql_equal("u.{$attribute->userfield}", ":value$i", false);
                $params["value$i"] = $value;
            }
        }

        if ($idp->tenantid && \tool_mulib\local\mulib::is_mutenancy_active()) {
            $wheres[] = 'u.tenantid = :tenantid';
            $params['tenantid'] = $idp->tenantid;
        }

        $sql = "SELECT u.* FROM {user} u $joins WHERE " . implode(' AND ', $wheres);
        $users = $DB->get_records_sql($sql, $params, 0, 2);
        if (count($users) !== 1) {
            if ($users) {
                saml::log('automatic mapping is ambiguous for IDP ' . $idp->id);
            }
            return null;
        }
        return reset($users);
    }

    /**
     * Create a new user account for the identity provider account.
     *
     * @param stdClass $idp
     * @param string $guid
     * @param array $bag
     * @return stdClass
     */
    public static function create_user(stdClass $idp, string $guid, array $bag): stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $values = attribute::get_values($idp, $bag, attribute::SYNC_ONCREATE);
        foreach (userfield::get_required_fields() as $field) {
            if (empty($values[$field])) {
                self::fail($idp, 'error_createmissing', $field);
            }
        }
        if ($values['username'] !== \core\user::clean_field($values['username'], 'username')) {
            self::fail($idp, 'error_createusername', $values['username']);
        }
        if ($DB->record_exists('user', ['username' => $values['username'], 'mnethostid' => $CFG->mnet_localhost_id])) {
            self::fail($idp, 'error_createusername', $values['username']);
        }
        if (!validate_email($values['email']) || email_is_not_allowed($values['email'])) {
            self::fail($idp, 'error_createemail', $values['email']);
        }
        // Duplicate emails are refused even when the site allows them, an account created
        // here must be identifiable by its email.
        $select = 'LOWER(email) = LOWER(:email) AND mnethostid = :mnethostid AND deleted = 0';
        $params = ['email' => $values['email'], 'mnethostid' => $CFG->mnet_localhost_id];
        if ($DB->record_exists_select('user', $select, $params)) {
            self::fail($idp, 'error_createemail', $values['email']);
        }

        $new = new stdClass();
        $new->auth = 'musaml';
        $new->confirmed = 1;
        $new->mnethostid = $CFG->mnet_localhost_id;
        $new->policyagreed = 0;
        foreach ($values as $field => $value) {
            if (!userfield::is_profile_field($field)) {
                $new->$field = $value;
            }
        }
        if ($idp->tenantid && \tool_mulib\local\mulib::is_mutenancy_active()) {
            $new->tenantid = $idp->tenantid;
        }

        $trans = $DB->start_delegated_transaction();
        $new->id = \core\user::create_user($new, false, false);
        $custom = array_filter($values, fn($field) => userfield::is_profile_field($field), ARRAY_FILTER_USE_KEY);
        if ($custom) {
            $data = [];
            foreach ($custom as $field => $value) {
                $data['profile_field_' . userfield::get_profile_shortname($field)] = $value;
            }
            $data['id'] = $new->id;
            profile_save_data((object)$data);
        }
        mapping::create((object)['idpid' => $idp->id, 'userid' => $new->id, 'guid' => $guid]);
        $trans->allow_commit();

        $user = $DB->get_record('user', ['id' => $new->id], '*', MUST_EXIST);
        \core\event\user_created::create_from_userid($user->id)->trigger();
        return $user;
    }

    /**
     * Details of the identity provider session, if the user arrived through SAML.
     *
     * @return array|null keys: idpid, nameid, nameidformat, sessionindex
     */
    public static function get_session(): ?array {
        global $SESSION;

        $session = $SESSION->auth_musaml_session ?? null;
        if (!is_array($session) || empty($session['idpid'])) {
            return null;
        }
        return $session;
    }

    /**
     * Start single logout at the identity provider, returns the URL to send the user to.
     *
     * Returns null when the identity provider has no logout service or is gone, the
     * local logout always happens either way.
     *
     * @return string|null
     */
    public static function get_logout_url(): ?string {
        global $CFG;

        $session = self::get_session();
        if (!$session) {
            return null;
        }
        $idp = idp::fetch($session['idpid']);
        if (!$idp || !$idp->logouturl) {
            return null;
        }
        $providerclass = provider::get_class($idp->provider);
        if (!$providerclass::supports_slo()) {
            return null;
        }

        try {
            $auth = saml::create_auth($idp);
            // The library would build a nonsense return address from the current script.
            return $auth->logout(
                $CFG->wwwroot . '/',
                [],
                $session['nameid'] ?? null,
                $session['sessionindex'] ?? null,
                true,
                $session['nameidformat'] ?? null
            );
        } catch (\Throwable $e) {
            // Logging out locally matters more than telling the identity provider.
            saml::log('cannot build logout request for IDP ' . $idp->id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Where to send the user after a local logout.
     *
     * Without single logout the identity provider still knows the visitor, so with
     * automatic login they would be signed straight back in. Sending them to the
     * login page with the redirect turned off leaves them logged out, as they asked.
     *
     * @return string|null null keeps the default logout target
     */
    public static function get_logout_redirect(): ?string {
        $session = self::get_session();
        if (!$session) {
            return null;
        }
        $idp = idp::fetch($session['idpid']);
        if (!$idp || !$idp->autologin) {
            return null;
        }
        return self::get_retry_url();
    }

    /**
     * Handle a logout message from the identity provider.
     *
     * @param stdClass $idp
     * @return string|null URL to redirect to, null when the library answered already
     * @throws moodle_exception when the message is not valid
     */
    public static function process_logout(stdClass $idp): ?string {
        $auth = saml::create_auth($idp);

        try {
            // The library reports malformed XML with a PHP warning before it throws.
            $url = @$auth->processSLO(false, null, false, [self::class, 'kill_idp_sessions'], true);
        } catch (\Throwable $e) {
            saml::log('logout message rejected for IDP ' . $idp->id . ': ' . $e->getMessage());
            throw new moodle_exception('error_logout', 'auth_musaml', '', $e->getMessage());
        }

        if ($auth->getErrors()) {
            $reason = $auth->getLastErrorReason() ?? implode(', ', $auth->getErrors());
            saml::log('logout message rejected for IDP ' . $idp->id . ': ' . $reason);
            throw new moodle_exception('error_logout', 'auth_musaml', '', $reason);
        }
        return $url ?: null;
    }

    /**
     * End the Moodle sessions of the user the identity provider is logging out.
     *
     * Called by the library while it processes a logout request.
     */
    public static function kill_idp_sessions(): void {
        global $USER;

        if (isloggedin() && !isguestuser()) {
            $userid = $USER->id;
            \core\session\manager::terminate_current();
            \core\session\manager::kill_user_sessions($userid);
        }
    }

    /**
     * Record the failure and stop with an error page.
     *
     * @param stdClass $idp
     * @param string $errorcode
     * @param string|null $detail
     * @param string|null $username
     * @return never
     */
    public static function fail(stdClass $idp, string $errorcode, ?string $detail = null, ?string $username = null): never {
        global $CFG;
        require_once($CFG->libdir . '/authlib.php');

        saml::log("login failed for IDP $idp->id: $errorcode " . ($detail ?? '') . ' ' . ($username ?? ''));

        // The plugin event carries the reason, the core event keeps the failed login report complete.
        \auth_musaml\event\login_failed::create_from_idp($idp, $errorcode, $detail)->trigger();

        if ($username !== null) {
            \core\event\user_login_failed::create([
                'other' => ['username' => $username, 'reason' => AUTH_LOGIN_FAILED],
            ])->trigger();
        }
        throw new moodle_exception($errorcode, 'auth_musaml', '', $detail);
    }

    /**
     * Attribute bag used for mapping: assertion attributes, NameID and optional short names.
     *
     * @param array $attributes name => list of values
     * @param string|null $nameid
     * @param stdClass $idp
     * @return array name => list of string values
     */
    public static function build_attribute_bag(array $attributes, ?string $nameid, stdClass $idp): array {
        $bag = [];
        foreach ($attributes as $name => $values) {
            $bag[$name] = array_values(array_map('strval', (array)$values));
        }
        if ($idp->attrsimple) {
            foreach ($attributes as $name => $values) {
                $short = self::get_short_name($name);
                if ($short !== $name && !array_key_exists($short, $bag)) {
                    $bag[$short] = array_values(array_map('strval', (array)$values));
                }
            }
        }
        if ($nameid !== null && $nameid !== '') {
            $bag[provider::NAMEID_ATTRIBUTE] = [$nameid];
        }

        $providerclass = provider::get_class($idp->provider);
        return $providerclass::filter_attributes($bag, $idp);
    }

    /**
     * Last segment of an URI style attribute name.
     *
     * @param string $name
     * @return string
     */
    public static function get_short_name(string $name): string {
        $short = preg_replace('#^.*[/:]#', '', $name);
        return $short === '' ? $name : $short;
    }

    /**
     * Value of the user ID attribute.
     *
     * @param array $bag
     * @param stdClass $idp
     * @return string|null
     */
    public static function get_guid(array $bag, stdClass $idp): ?string {
        $values = $bag[$idp->mapattr] ?? [];
        $values = array_values(array_filter($values, fn($v) => trim((string)$v) !== ''));
        if (count($values) !== 1) {
            return null;
        }
        return trim($values[0]);
    }
}
