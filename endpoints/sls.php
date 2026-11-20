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
/** @var core_renderer $OUTPUT */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

// phpcs:disable moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../../config.php');

$idpid = optional_param('idpid', 0, PARAM_INT);

// A logout message always carries one of these. Without one this is not the identity
// provider talking, so nothing may end the session, a plain link must not log anybody out.
$response = $_GET['SAMLResponse'] ?? $_POST['SAMLResponse'] ?? null;
if (!isset($_GET['SAMLRequest']) && !is_string($response)) {
    redirect(new \core\url('/'));
}

$PAGE->set_url('/auth/musaml/endpoints/sls.php');
$PAGE->set_context(\core\context\system::instance());

$home = (new \core\url('/'))->out(false);

$fail = function (string $message) use ($PAGE, $OUTPUT, $home): never {
    require_logout();
    $PAGE->set_pagelayout('login');
    $PAGE->set_title(get_string('logout'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification($message, 'warning', false);
    echo $OUTPUT->continue_button($home);
    echo $OUTPUT->footer();
    die;
};

// The identity provider of the current session, or the one named in the request.
$session = login::get_session();
$idp = null;
if ($session) {
    $idp = idp::fetch($session['idpid']);
}
if (!$idp && $idpid) {
    $idp = idp::fetch($idpid);
}
if (!$idp && is_string($response)) {
    $idp = login::get_logout_response_idp($response);
}
if (!$idp || !saml::has_sp_certificate()) {
    redirect($home);
}

if (is_string($response)) {
    try {
        login::process_logout_response($idp, $response, isset($_GET['SAMLResponse']));
    } catch (moodle_exception $e) {
        $fail($e->getMessage());
    }
    redirect($home);
}

try {
    $url = login::process_logout($idp);
} catch (moodle_exception $e) {
    // The identity provider did send a message, it just cannot be verified. Ending the
    // local session is still right, the user must never be left with a session the
    // identity provider believes is gone.
    $fail($e->getMessage());
}

redirect($url ?: $home);
