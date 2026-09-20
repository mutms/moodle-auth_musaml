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

/**
 * SAML plugin.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\attribute;
use auth_musaml\local\idp;
use auth_musaml\local\login;
use auth_musaml\local\saml;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * SAML authentication plugin.
 */
final class auth_plugin_musaml extends auth_plugin_base {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'musaml';
        $this->config = get_config('auth_musaml');
    }

    /**
     * Returns false if this plugin is enabled but not configured.
     *
     * @return bool
     */
    public function is_configured(): bool {
        return saml::has_sp_certificate();
    }

    /**
     * Sends visitors straight to the only identity provider when it asks for it.
     *
     * Runs before a visitor of a protected page is sent to the login page.
     */
    public function pre_loginpage_hook(): void {
        $this->autologin_redirect();
    }

    /**
     * Sends visitors straight to the only identity provider when it asks for it.
     *
     * Runs on the login page itself.
     */
    public function loginpage_hook(): void {
        $this->autologin_redirect();
    }

    /**
     * Redirect to the identity provider when exactly one asks for it.
     */
    protected function autologin_redirect(): void {
        global $SESSION, $CFG;

        $idp = login::get_autologin_idp();
        if (!$idp) {
            return;
        }
        login::count_autologin_redirect();
        $wantsurl = '';
        if (!empty($SESSION->wantsurl)) {
            $wantsurl = str_replace($CFG->wwwroot, '', $SESSION->wantsurl);
        }
        redirect(login::start($idp, $wantsurl));
    }

    /**
     * Is SAML data synchornised during login?
     *
     * @return bool true means automatically copy data from ext to user table
     */
    public function is_synchronised_with_external(): bool {
        return attribute::is_synchronised_on_login();
    }

    /**
     * Returns enabled IDPs for the login page.
     *
     * @param string $wantsurl
     * @return array
     */
    public function loginpage_idp_list($wantsurl): array {
        global $OUTPUT;

        if (!$this->is_configured()) {
            return [];
        }
        $wantsurl = (string)$wantsurl;
        $result = [];
        foreach (idp::get_enabled(login::get_current_tenantid()) as $idp) {
            $providerclass = \auth_musaml\local\provider\base::get_class($idp->provider);
            $icon = $providerclass::get_icon();
            $result[] = [
                'url' => login::get_login_url($idp, $wantsurl),
                'iconurl' => $OUTPUT->image_url($icon->pix, $icon->component),
                'name' => format_string($idp->name),
            ];
        }
        return $result;
    }

    /**
     * Tell the identity provider that the user is logging out.
     */
    public function logoutpage_hook(): void {
        global $redirect;

        // The local session is ended by core right after this hook.
        $url = login::get_logout_url() ?? login::get_logout_redirect();
        if ($url) {
            $redirect = $url;
        }
    }

    /**
     * SAML works only via SSO, no password-based login allowed.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function user_login($username, $password): bool {
        return false;
    }

    /**
     * No local passwords allowed.
     *
     * @return true
     */
    public function prevent_local_passwords(): bool {
        return true;
    }

    /**
     * SAML is external type auth.
     *
     * @return bool
     */
    public function is_internal(): bool {
        return false;
    }

    /**
     * No password changes possible - IDP deals with that.
     *
     * @return bool
     */
    public function can_change_password(): bool {
        return false;
    }

    /**
     * No password changes possible - IDP deals with that.
     *
     * @return null
     */
    public function change_password_url() {
        return null;
    }

    /**
     * No password changes possible - IDP deals with that.
     *
     * @return bool
     */
    public function can_reset_password(): bool {
        return false;
    }

    /**
     * Manual migration to musaml is supported,
     * it requires either manual mapping or enabled automapping.
     *
     * @return bool
     */
    public function can_be_manually_set(): bool {
        return true;
    }
}
