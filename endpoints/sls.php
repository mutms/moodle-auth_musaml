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
 * SP Single Logout Service endpoint.
 *
 * Handles both the answer to a logout we started and a logout the identity
 * provider starts on its own.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\login;
use auth_musaml\local\saml;
use core\exception\moodle_exception;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

// phpcs:disable moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../../config.php');

$idpid = optional_param('idpid', 0, PARAM_INT);

// A logout message always carries one of these. Without one this is not the identity
// provider talking, so nothing may end the session, a plain link must not log anybody out.
if (!isset($_GET['SAMLRequest']) && !isset($_GET['SAMLResponse'])) {
    redirect(new \core\url('/'));
}

$PAGE->set_url('/auth/musaml/endpoints/sls.php');
$PAGE->set_context(\core\context\system::instance());

$home = (new \core\url('/'))->out(false);

// The identity provider of the current session, or the one named in the request.
$session = login::get_session();
$idp = null;
if ($session) {
    $idp = idp::fetch($session['idpid']);
}
if (!$idp && $idpid) {
    $idp = idp::fetch($idpid);
}
if (!$idp || !saml::has_sp_certificate()) {
    redirect($home);
}

try {
    $url = login::process_logout($idp);
} catch (moodle_exception $e) {
    // The identity provider did send a message, it just cannot be verified. Ending the
    // local session is still right, the user must never be left with a session the
    // identity provider believes is gone.
    \core\notification::warning($e->getMessage());
    require_logout();
    redirect($home);
}

redirect($url ?: $home);
