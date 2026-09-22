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

require_login();
require_capability('auth/musaml:managemappings', \core\context\system::instance());

$idp = $DB->get_record('auth_musaml_idp', ['id' => $idpid], '*', MUST_EXIST);

$pageurl = new \core\url('/auth/musaml/management/user_upload.php', ['id' => $idp->id]);
idp::setup_mappings_page($idp, $pageurl);
$PAGE->navbar->add(get_string('import', 'auth_musaml'));

$returnurl = new \core\url('/auth/musaml/management/users.php', ['id' => $idp->id]);

$draftid = file_get_submitted_draft_itemid('sourcefile');

// The stored document is the state of the wizard, each stage fills one part of it.
$csvdata = mapping_import::get_data($draftid);

// Stage one, where the data comes from.
if (mapping_import::is_source_stage($csvdata)) {
    $form = new user_upload_source(null, ['idp' => $idp]);
    if ($form->is_cancelled()) {
        mapping_import::delete_data($draftid);
        redirect($returnurl);
    }
    if ($data = $form->get_data()) {
        $csvdata = mapping_import::save_source($data);
        $draftid = (int)$data->sourcefile;
    }
    if (mapping_import::is_source_stage($csvdata)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('import', 'auth_musaml'));
        $form->display();
        echo $OUTPUT->footer();
        die;
    }
}

// Stage two, what each column means.
if (mapping_import::is_columns_stage($csvdata)) {
    $form = new user_upload_columns(null, ['idp' => $idp, 'csvdata' => $csvdata, 'draftid' => $draftid]);
    if ($form->is_cancelled()) {
        mapping_import::delete_data($draftid);
        redirect($returnurl);
    }
    if ($data = $form->get_data()) {
        $csvdata = mapping_import::save_columns($csvdata, $data);
    }
    if (mapping_import::is_columns_stage($csvdata)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('import_columns', 'auth_musaml'));
        $form->display();
        echo $OUTPUT->footer();
        die;
    }
}

// Stage three, the options with their own dry run.
if (mapping_import::is_options_stage($csvdata)) {
    $form = new user_upload_options(null, ['idp' => $idp, 'csvdata' => $csvdata, 'draftid' => $draftid]);
    if ($form->is_cancelled()) {
        mapping_import::delete_data($draftid);
        redirect($returnurl);
    }
    if ($data = $form->get_data()) {
        $csvdata = mapping_import::save_options($csvdata, $data);
    }
    if (mapping_import::is_options_stage($csvdata)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('import_options', 'auth_musaml'));
        $form->display();
        echo $OUTPUT->footer();
        die;
    }
}

// Everything was answered, write the mappings.
$counts = mapping_import::import($idp, $csvdata, (object)$csvdata->options);
mapping_import::delete_data($draftid);
if ($counts[mapping_import::RESULT_ERROR]) {
    \core\notification::error(get_string('import_failed', 'auth_musaml', (object)$counts));
} else {
    \core\notification::success(get_string('import_done', 'auth_musaml', (object)$counts));
}
redirect($returnurl);
