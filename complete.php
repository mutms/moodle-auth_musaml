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
 * Finish a login that the ACS endpoint accepted.
 *
 * The endpoint runs without a session because the response arrives from the identity
 * provider site. This page is reached by a redirect inside our own site, so the Moodle
 * session cookie is sent again and the user can be logged in here.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\login;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

// phpcs:disable moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$PAGE->set_url('/auth/musaml/complete.php');
$PAGE->set_context(\core\context\system::instance());

$finished = login::pop_result();

if (!$finished) {
    \core\notification::error(get_string('error_norequest', 'auth_musaml'));
    redirect(login::get_retry_url());
}

$request = $finished['request'];
$result = $finished['result'];

if (isset($result['error'])) {
    \core\notification::error($result['error']);
    redirect(login::get_retry_url());
}

$idp = idp::fetch($request->idpid);
$user = $request->userid ? \core\user::get_user($request->userid) : null;
if (!$idp || !$user) {
    \core\notification::error(get_string('error_norequest', 'auth_musaml'));
    redirect(login::get_retry_url());
}

$samlsession = [
    'nameid' => $result['nameid'] ?? null,
    'nameidformat' => $result['nameidformat'] ?? null,
    'sessionindex' => $result['sessionindex'] ?? null,
];

redirect(login::finish($idp, $user, $result['bag'] ?? [], $samlsession, (string)$request->wantsurl));
