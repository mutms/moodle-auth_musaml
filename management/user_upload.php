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
 * Upload or copy/paste multiple user mappings for one IDP via CSV format.
 *
 * The page walks through three stages: source, column meaning, options with a dry run.
 * Parsed rows live in a file area between stages, only the draft id travels in the form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\form\user_upload_columns;
use auth_musaml\local\form\user_upload_options;
use auth_musaml\local\form\user_upload_source;
use auth_musaml\local\idp;
use auth_musaml\local\mapping_import;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var core_renderer $OUTPUT */
/** @var moodle_page $PAGE */
/** @var moodle_database $DB */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

$idpid = required_param('id', PARAM_INT);
$draftid = optional_param('draftid', 0, PARAM_INT);
$stage = optional_param('stage', 'source', PARAM_ALPHA);

require_login();
require_capability('auth/musaml:managemappings', \core\context\system::instance());

$idp = $DB->get_record('auth_musaml_idp', ['id' => $idpid], '*', MUST_EXIST);

$pageurl = new \core\url('/auth/musaml/management/user_upload.php', ['id' => $idp->id]);
idp::setup_mappings_page($idp, $pageurl);
$PAGE->navbar->add(get_string('import', 'auth_musaml'));

$returnurl = new \core\url('/auth/musaml/management/users.php', ['id' => $idp->id]);

$rows = mapping_import::get_data($draftid);
if (!$rows) {
    $draftid = 0;
    $stage = 'source';
}

// Forms post back to their own stage, otherwise the wizard would start over.
$stageurl = new \core\url($pageurl, ['draftid' => $draftid, 'stage' => $stage]);

// Stage one, where the data comes from.
if ($stage === 'source') {
    $form = new user_upload_source($stageurl->out(false), ['idp' => $idp]);
    if ($form->is_cancelled()) {
        redirect($returnurl);
    }
    if ($data = $form->get_data()) {
        $draftid = (int)($data->csvfile ?: -1);
        redirect(new \core\url($pageurl, ['draftid' => $draftid, 'stage' => 'columns']));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('import', 'auth_musaml'));
    $form->display();
    echo $OUTPUT->footer();
    die;
}

// Stage two, what each column means.
if ($stage === 'columns') {
    $customdata = ['idp' => $idp, 'rows' => $rows, 'draftid' => $draftid];
    $form = new user_upload_columns($stageurl->out(false), $customdata);
    if ($form->is_cancelled()) {
        mapping_import::delete_data($draftid);
        redirect($returnurl);
    }
    if ($data = $form->get_data()) {
        $params = ['draftid' => $draftid, 'stage' => 'options'];
        foreach ($rows[0] as $index => $unused) {
            $params['column_' . $index] = $data->{'column_' . $index};
        }
        redirect(new \core\url($pageurl, $params));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('import_columns', 'auth_musaml'));
    $form->display();
    echo $OUTPUT->footer();
    die;
}

// Stage three, options with a dry run before anything is written.
$columns = [];
foreach ($rows[0] as $index => $unused) {
    $columns[$index] = optional_param('column_' . $index, mapping_import::COLUMN_IGNORE, PARAM_ALPHANUMEXT);
}

// The confirmation button posts the checked options back, the form itself is not resubmitted.
$options = new stdClass();
foreach (array_keys(mapping_import::get_skip_menu()) as $name) {
    $options->$name = optional_param($name, 0, PARAM_BOOL);
}
$options->allowotherauth = optional_param('allowotherauth', 0, PARAM_BOOL);
$options->setauth = optional_param('setauth', 0, PARAM_BOOL);

if (optional_param('confirm', 0, PARAM_BOOL)) {
    require_sesskey();
    $counts = mapping_import::import($idp, $rows, $columns, $options);
    mapping_import::delete_data($draftid);
    if ($counts[mapping_import::RESULT_ERROR]) {
        \core\notification::error(get_string('import_failed', 'auth_musaml', (object)$counts));
    } else {
        \core\notification::success(get_string('import_done', 'auth_musaml', (object)$counts));
    }
    redirect($returnurl);
}

$customdata = ['idp' => $idp, 'draftid' => $draftid, 'columns' => $columns];
$form = new user_upload_options($stageurl->out(false), $customdata);
if ($form->is_cancelled()) {
    mapping_import::delete_data($draftid);
    redirect($returnurl);
}

$outcomes = null;
if ($data = $form->get_data()) {
    $options = $data;
    $outcomes = mapping_import::check($idp, $rows, $columns, $options);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('import_options', 'auth_musaml'));
$form->display();

if ($outcomes !== null) {
    $counts = [
        mapping_import::RESULT_CREATED => 0,
        mapping_import::RESULT_SKIPPED => 0,
        mapping_import::RESULT_ERROR => 0,
    ];
    foreach ($outcomes as $outcome) {
        $counts[$outcome->result]++;
    }

    echo $OUTPUT->heading(get_string('import_preview', 'auth_musaml'), 3);
    $countmessage = get_string('import_counts', 'auth_musaml', (object)$counts);
    $countstyle = $counts[mapping_import::RESULT_ERROR] ? 'error' : 'info';
    echo $OUTPUT->notification($countmessage, $countstyle, false);

    $labels = [
        mapping_import::RESULT_CREATED => get_string('import_result_created', 'auth_musaml'),
        mapping_import::RESULT_SKIPPED => get_string('import_result_skipped', 'auth_musaml'),
        mapping_import::RESULT_ERROR => get_string('import_result_error', 'auth_musaml'),
    ];
    $table = new html_table();
    $table->head = [
        get_string('user_mapping_guid', 'auth_musaml'),
        get_string('username'),
        get_string('status'),
        get_string('description'),
    ];
    foreach ($outcomes as $outcome) {
        $class = $outcome->result === mapping_import::RESULT_ERROR ? 'text-danger' : '';
        $table->data[] = [
            s($outcome->guid),
            s($outcome->username),
            \core\output\html_writer::span($labels[$outcome->result], $class),
            s($outcome->message),
        ];
    }
    echo \core\output\html_writer::table($table);

    if (!$counts[mapping_import::RESULT_ERROR] && $counts[mapping_import::RESULT_CREATED]) {
        $params = ['id' => $idp->id, 'draftid' => $draftid, 'stage' => 'options'];
        $params['confirm'] = 1;
        $params['sesskey'] = sesskey();
        foreach ($columns as $index => $value) {
            $params['column_' . $index] = $value;
        }
        foreach (array_keys(mapping_import::get_skip_menu()) as $name) {
            $params[$name] = (int)!empty($options->$name);
        }
        $params['allowotherauth'] = (int)!empty($options->allowotherauth);
        $params['setauth'] = (int)!empty($options->setauth);
        $confirmurl = new \core\url('/auth/musaml/management/user_upload.php', $params);
        echo $OUTPUT->single_button($confirmurl, get_string('import_confirm', 'auth_musaml'), 'post');
    }
}

echo $OUTPUT->footer();
