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
 * Start SAML login with one IDP.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\login;
use auth_musaml\local\saml;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

// phpcs:disable moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$wantsurl = optional_param('wantsurl', '', PARAM_LOCALURL);
$test = optional_param('test', 0, PARAM_BOOL);

$PAGE->set_url('/auth/musaml/login.php', ['id' => $id]);
$PAGE->set_context(\core\context\system::instance());

if (!is_enabled_auth('musaml') || !saml::has_sp_certificate()) {
    \core\notification::error(get_string('error_spnotconfigured', 'auth_musaml'));
    redirect(login::get_retry_url());
}

$idp = idp::fetch($id);
if (!$idp || !login::is_idp_available($idp)) {
    \core\notification::error(get_string('error_idpunavailable', 'auth_musaml'));
    redirect(login::get_retry_url());
}

if ($test) {
    // Site admin checks what the IDP sends without logging anybody in.
    require_login();
    require_capability('moodle/site:config', \core\context\system::instance());
    require_sesskey();
    redirect(login::start($idp, '', true));
}

if (isloggedin() && !isguestuser()) {
    redirect(new \core\url('/'));
}

redirect(login::start($idp, $wantsurl));
