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
 * Display user mappings for one IDP.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\mapping;
use tool_mulib\output\ajax_form\button;
use tool_mulib\output\ajax_form\icon;
use tool_mulib\output\header_actions;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var core_renderer $OUTPUT */
/** @var moodle_page $PAGE */
/** @var moodle_database $DB */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();
require_capability('auth/musaml:managemappings', \core\context\system::instance());

$idp = $DB->get_record('auth_musaml_idp', ['id' => $id], '*', MUST_EXIST);

$pageurl = new \core\url('/auth/musaml/management/users.php', ['id' => $idp->id]);
idp::setup_mappings_page($idp, $pageurl);

$actions = new header_actions(get_string('actions'));
$url = new \core\url('/auth/musaml/management/user_create.php', ['idpid' => $idp->id]);
$addbutton = new button($url, get_string('user_mapping_create', 'auth_musaml'), true);
$addbutton->set_submitted_action($addbutton::SUBMITTED_ACTION_RELOAD);
$actions->add_button($addbutton);
$url = new \core\url('/auth/musaml/management/user_upload.php', ['id' => $idp->id]);
$actions->get_dropdown()->add_item(get_string('import', 'auth_musaml'), $url);
$PAGE->add_header_action($OUTPUT->render($actions));

echo $OUTPUT->header();

$mappings = mapping::get_for_idp($idp->id);
if (!$mappings) {
    echo $OUTPUT->notification(get_string('user_mappings_none', 'auth_musaml'), 'info', false);
} else {
    $yesno = fn(int $value): string => $value ? get_string('yes') : get_string('no');
    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('username'),
        get_string('user_mapping_guid', 'auth_musaml'),
        get_string('user_mapping_allowotherauth', 'auth_musaml'),
        get_string('user_mapping_automapped', 'auth_musaml'),
        get_string('user_mapping_timecreated', 'auth_musaml'),
        get_string('actions'),
    ];
    foreach ($mappings as $m) {
        $userurl = new \core\url('/user/profile.php', ['id' => $m->userid]);
        $url = new \core\url('/auth/musaml/management/user_update.php', ['id' => $m->id]);
        $edit = new icon($url, get_string('user_mapping_update', 'auth_musaml'), 't/edit');
        $edit->set_submitted_action($edit::SUBMITTED_ACTION_RELOAD);

        $url = new \core\url('/auth/musaml/management/user_delete.php', ['id' => $m->id]);
        $delete = new icon($url, get_string('user_mapping_delete', 'auth_musaml'), 't/delete');
        $delete->set_submitted_action($delete::SUBMITTED_ACTION_RELOAD);
        $delete->set_form_size('sm');
        $delete->add_class('text-danger');

        $table->data[] = [
            \core\output\html_writer::link($userurl, fullname($m)),
            s($m->username),
            s($m->guid),
            $yesno($m->allowotherauth),
            $yesno($m->automapped),
            userdate($m->timecreated),
            $OUTPUT->render($edit) . ' ' . $OUTPUT->render($delete),
        ];
    }
    echo \core\output\html_writer::table($table);
}

echo $OUTPUT->footer();
