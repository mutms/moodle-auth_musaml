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
 * Result of a test login, nobody is logged in by it.
 *
 * The ACS endpoint runs without a session, so it only stores what the identity provider
 * sent and redirects here, where the administrator session is available again.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\login;
use core\output\html_writer;
use tool_mulib\output\entity_details;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var core_renderer $OUTPUT */
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', \core\context\system::instance());

$idpsurl = new \core\url('/auth/musaml/management/idps.php');
$error = \core\output\notification::NOTIFY_ERROR;

$finished = login::take_test_result();
if (!$finished) {
    redirect($idpsurl, get_string('error_norequest', 'auth_musaml'), null, $error);
}

$request = $finished['request'];
$result = $finished['result'];
$idp = idp::fetch($request->idpid);
if (!$idp) {
    redirect($idpsurl, get_string('error_idpunavailable', 'auth_musaml'), null, $error);
}

$backurl = new \core\url('/auth/musaml/management/idp.php', ['id' => $idp->id]);
$pageurl = new \core\url('/auth/musaml/management/test_login.php');
idp::setup_page($idp, $pageurl, 'idp_details');
$PAGE->navbar->add(get_string('test_login', 'auth_musaml'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('test_login', 'auth_musaml'));

if (isset($result['error'])) {
    echo $OUTPUT->notification($result['error'], 'error', false);
    echo $OUTPUT->single_button($backurl, get_string('back'), 'get');
    echo $OUTPUT->footer();
    die;
}

// The attributes are kept with the pending login until it expires, so the mapping form
// can show them, and two administrators testing at once never see each other's values.
$bag = $result['bag'] ?? [];
login::remember_test_result($idp->id, $request->id);

echo $OUTPUT->notification(get_string('test_login_success', 'auth_musaml'), 'success', false);

$guid = login::get_guid($bag, $idp);
$details = new entity_details();
$guidtext = $guid === null ? html_writer::span(get_string('none'), 'text-danger') : s($guid);
$details->add(get_string('idp_mapattr', 'auth_musaml') . ' (' . s($idp->mapattr) . ')', $guidtext);
if ($request->userid) {
    $user = \core\user::get_user($request->userid);
    $userurl = new \core\url('/user/profile.php', ['id' => $request->userid]);
    $userlink = html_writer::link($userurl, fullname($user) . ' (' . s($user->username) . ')');
    $details->add(get_string('test_login_user', 'auth_musaml'), $userlink);
} else {
    $details->add(get_string('test_login_user', 'auth_musaml'), get_string('test_login_nouser', 'auth_musaml'));
}
$details->add(get_string('idp_nameidformat', 'auth_musaml'), s((string)($result['nameidformat'] ?? '')));
$details->add('SessionIndex', s((string)($result['sessionindex'] ?? '')));
echo $OUTPUT->render($details);

$table = new html_table();
$table->attributes['class'] = 'generaltable mb-0';
$table->head = [get_string('attribute_idpattr', 'auth_musaml'), get_string('attribute_value', 'auth_musaml')];
foreach ($bag as $name => $values) {
    $table->data[] = [s($name), s(implode(', ', $values))];
}
$body = $OUTPUT->heading(get_string('test_login_attributes', 'auth_musaml'), 3);
$body .= html_writer::table($table);
echo html_writer::div(html_writer::div($body, 'card-body'), 'card mt-4 mb-3');

echo $OUTPUT->single_button($backurl, get_string('back'), 'get');
echo $OUTPUT->footer();
