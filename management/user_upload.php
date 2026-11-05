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
 * The parsed rows and the answers live in the muform wizard state between stages, the
 * current stage is always decided from that state.
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
use tool_mulib\muform\wizard;

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

// The state belongs to this identity provider, it cannot be replayed against another one.
$wizardname = 'auth_musaml_user_upload:' . $idp->id;
$stages = [
    'source' => get_string('import', 'auth_musaml'),
    'columns' => get_string('import_columns', 'auth_musaml'),
    'options' => get_string('import_options', 'auth_musaml'),
];
$wizard = wizard::load($wizardname, $stages);
if (!$wizard) {
    $wizard = wizard::start($wizardname, $stages);
    redirect($wizard->get_url($pageurl));
}

// The stored state is validated again on every request, each stage fills one part of it.
$state = $wizard->get_data();
$csvdata = mapping_import::validate($state);
$source = is_array($state['source'] ?? null) ? $state['source'] : [];

$isvalid = function (string $stage) use ($csvdata): bool {
    return match ($stage) {
        'source' => mapping_import::is_source_valid($csvdata),
        'columns' => mapping_import::is_columns_valid($csvdata),
        'options' => mapping_import::is_options_valid($csvdata),
    };
};
$stage = $wizard->resolve_stages($isvalid);

if ($wizard->is_finished()) {
    // Everything was answered, write the mappings.
    $counts = mapping_import::import($idp, $csvdata, (object)$csvdata->options);
    $wizard->delete();
    if ($counts[mapping_import::RESULT_ERROR]) {
        \core\notification::error(get_string('import_failed', 'auth_musaml', (object)$counts));
    } else {
        \core\notification::success(get_string('import_done', 'auth_musaml', (object)$counts));
    }
    redirect($returnurl);
}

$formurl = $wizard->get_url($pageurl, $stage);
if ($stage === 'source') {
    $form = new user_upload_source($formurl, $source);
} else if ($stage === 'columns') {
    $current = [];
    if ($csvdata->columns) {
        $current['headers'] = (int)$csvdata->columns['headers'];
        foreach ($csvdata->columns['map'] as $index => $value) {
            $current['column_' . $index] = $value;
        }
    }
    $form = new user_upload_columns($formurl, $current, ['rows' => $csvdata->rows]);
} else {
    // Options of an earlier confirmation are only defaults of the form.
    $form = new user_upload_options($formurl, $csvdata->options, ['idp' => $idp, 'csvdata' => $csvdata]);
}

if ($form->is_cancelled()) {
    $wizard->delete();
    redirect($returnurl);
}
if ($form->is_reloaded() && $wizard->get_previous_stage() !== null && $form->get_element('back')->get_value()) {
    redirect($wizard->get_url($pageurl, $wizard->get_previous_stage()));
}
if ($data = $form->get_data()) {
    if ($stage === 'source') {
        $csvdata = mapping_import::save_source($form->get_source_content(), $data->encoding, $data->delimiter_name);
        $source = [
            'sourcefile' => (int)$data->sourcefile,
            'csvtext' => $data->csvtext,
            'encoding' => $data->encoding,
            'delimiter_name' => $data->delimiter_name,
        ];
    } else if ($stage === 'columns') {
        $csvdata = mapping_import::save_columns($csvdata, $data);
    } else {
        $csvdata = mapping_import::save_options($csvdata, $data);
    }
    $wizard->complete_stage($stage, (array)$csvdata + ['source' => $source]);
    redirect($wizard->get_url($pageurl));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($stages[$stage], 2, 'mb-3');
echo $wizard->render($OUTPUT, $pageurl, $form);
echo $OUTPUT->footer();
