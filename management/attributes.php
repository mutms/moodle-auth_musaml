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
 * Display synchronised user attributes for one IDP.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\attribute;
use auth_musaml\local\idp;
use auth_musaml\local\login;
use auth_musaml\local\saml;
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
require_capability('moodle/site:config', \core\context\system::instance());

$idp = $DB->get_record('auth_musaml_idp', ['id' => $id], '*', MUST_EXIST);

$pageurl = new \core\url('/auth/musaml/management/attributes.php', ['id' => $idp->id]);
idp::setup_page($idp, $pageurl, 'idp_attributes');

$actions = new header_actions(get_string('actions'));
$url = new \core\url('/auth/musaml/management/attribute_create.php', ['idpid' => $idp->id]);
$addbutton = new button($url, get_string('attribute_create', 'auth_musaml'), true);
$addbutton->set_submitted_action($addbutton::SUBMITTED_ACTION_RELOAD);
$actions->add_button($addbutton);

// Mapping attributes without seeing what arrives is guesswork, keep the test at hand.
if ($idp->enabled && saml::has_sp_certificate()) {
    $url = new \core\url('/auth/musaml/login.php', ['id' => $idp->id, 'test' => 1, 'sesskey' => sesskey()]);
    $actions->get_dropdown()->add_item(get_string('test_login', 'auth_musaml'), $url);
}

$PAGE->add_header_action($OUTPUT->render($actions));

echo $OUTPUT->header();

$fieldmenu = \auth_musaml\local\userfield::get_menu();

$attributes = attribute::get_for_idp($idp->id);
if (!$attributes) {
    echo $OUTPUT->notification(get_string('attributes_none', 'auth_musaml'), 'info', false);
} else {
    $syncmenu = attribute::get_sync_menu();
    $yesno = fn(int $value): string => $value ? get_string('yes') : get_string('no');
    $table = new html_table();
    $table->head = [
        get_string('attribute_userfield', 'auth_musaml'),
        get_string('attribute_idpattr', 'auth_musaml'),
        get_string('attribute_sync', 'auth_musaml'),
        get_string('attribute_usermapping', 'auth_musaml'),
        get_string('actions'),
    ];
    foreach ($attributes as $attribute) {
        $url = new \core\url('/auth/musaml/management/attribute_update.php', ['id' => $attribute->id]);
        $edit = new icon($url, get_string('attribute_update', 'auth_musaml'), 't/edit');
        $edit->set_submitted_action($edit::SUBMITTED_ACTION_RELOAD);

        $url = new \core\url('/auth/musaml/management/attribute_delete.php', ['id' => $attribute->id]);
        $delete = new icon($url, get_string('attribute_delete', 'auth_musaml'), 't/delete');
        $delete->set_submitted_action($delete::SUBMITTED_ACTION_RELOAD);
        $delete->set_form_size('sm');
        $delete->add_class('text-danger');

        $label = $fieldmenu[$attribute->userfield] ?? $attribute->userfield;
        $name = \core\output\html_writer::span('(' . s($attribute->userfield) . ')', 'text-muted small');
        $table->data[] = [
            s($label) . ' ' . $name,
            s($attribute->idpattr),
            $syncmenu[$attribute->sync] ?? $attribute->sync,
            $yesno($attribute->usermapping),
            $OUTPUT->render($edit) . ' ' . $OUTPUT->render($delete),
        ];
    }
    echo \core\output\html_writer::table($table);
}

// Values from the last test login of this administrator, the names to map are right here.
$test = login::get_test_attributes($idp->id);
if ($test) {
    $mapped = [];
    foreach ($attributes as $attribute) {
        $mapped[$attribute->idpattr][] = $fieldmenu[$attribute->userfield] ?? $attribute->userfield;
    }

    $table = new html_table();
    $table->attributes['class'] = 'generaltable mb-0';
    $table->head = [
        get_string('attribute_idpattr', 'auth_musaml'),
        get_string('attribute_value', 'auth_musaml'),
        get_string('attribute_userfield', 'auth_musaml'),
    ];
    foreach ($test['bag'] as $name => $values) {
        if (isset($mapped[$name])) {
            $action = s(implode(', ', $mapped[$name]));
        } else {
            // One click turns a received attribute into a mapping.
            $params = ['idpid' => $idp->id, 'idpattr' => $name];
            $url = new \core\url('/auth/musaml/management/attribute_create.php', $params);
            $add = new icon($url, get_string('attribute_create', 'auth_musaml'), 't/add');
            $add->set_submitted_action($add::SUBMITTED_ACTION_RELOAD);
            $action = $OUTPUT->render($add);
        }
        $table->data[] = [s($name), s(implode(', ', $values)), $action];
    }

    $ago = format_time(time() - $test['time']);
    $body = $OUTPUT->heading(get_string('test_login_attributes', 'auth_musaml'), 3);
    $body .= \core\output\html_writer::div(get_string('test_attributes_info', 'auth_musaml', $ago), 'text-muted mb-2');
    $body .= \core\output\html_writer::table($table);
    echo \core\output\html_writer::div(\core\output\html_writer::div($body, 'card-body'), 'card mt-4');
}

echo $OUTPUT->footer();
