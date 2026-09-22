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

namespace auth_musaml\local\form;

use auth_musaml\local\mapping_import;

/**
 * Mapping import, stage three: options and the dry run result.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_upload_options extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $idp = $this->_customdata['idp'];
        $csvdata = $this->_customdata['csvdata'];
        $draftid = $this->_customdata['draftid'];

        // The form action carries no query string, so the page needs the id back.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $idp->id);

        // Same name the file picker of the first stage submits, so the draft area of
        // the import travels with every stage without touching the URL.
        $mform->addElement('hidden', 'sourcefile');
        $mform->setType('sourcefile', PARAM_INT);
        $mform->setConstant('sourcefile', $draftid);

        // What happens to the accounts comes first, the skip list below depends on it.
        $mform->addElement('advcheckbox', 'allowotherauth', get_string('user_mapping_allowotherauth', 'auth_musaml'));
        $mform->addHelpButton('allowotherauth', 'user_mapping_allowotherauth', 'auth_musaml');

        $mform->addElement('advcheckbox', 'setauth', get_string('user_mapping_setauth', 'auth_musaml'));
        $mform->setDefault('setauth', 1);
        $mform->addHelpButton('setauth', 'user_mapping_setauth', 'auth_musaml');

        $mform->addElement('static', 'skipinfo', '', get_string('import_skip_info', 'auth_musaml'));
        foreach (mapping_import::get_skip_menu() as $name => $label) {
            $mform->addElement('advcheckbox', $name, $label);
            $mform->setDefault($name, 1);
        }

        // An import of a document of an earlier import brings its own options.
        $this->set_data((object)$csvdata->options);

        $mform->registerNoSubmitButton('refresh');
        $mform->addElement('submit', 'refresh', get_string('import_refresh', 'auth_musaml'));

        $mform->addElement('static', 'preview', '', '');

        $this->add_action_buttons(true, get_string('import_confirm', 'auth_musaml'));
    }

    #[\Override]
    public function definition_after_data(): void {
        parent::definition_after_data();

        $mform = $this->_form;
        $idp = $this->_customdata['idp'];
        $csvdata = $this->_customdata['csvdata'];

        // The preview always shows what the options in the form would do right now.
        $options = (object)[];
        foreach (mapping_import::get_option_names() as $name) {
            $element = $mform->getElement($name);
            $options->$name = (int)(bool)$element->getValue();
        }

        $outcomes = mapping_import::check($idp, $csvdata, $options);
        $mform->getElement('preview')->setValue($this->render_preview($outcomes));

        if (!$this->is_importable($outcomes)) {
            $mform->getElement('buttonar')->getElements()[0]->updateAttributes(['disabled' => 'disabled']);
        }
    }

    /**
     * May the import run with the outcomes of the dry run?
     *
     * @param array $outcomes
     * @return bool
     */
    private function is_importable(array $outcomes): bool {
        $created = 0;
        foreach ($outcomes as $outcome) {
            if ($outcome->result === mapping_import::RESULT_ERROR) {
                return false;
            }
            if ($outcome->result === mapping_import::RESULT_CREATED) {
                $created++;
            }
        }
        return (bool)$created;
    }

    /**
     * Counts of the dry run and the first rows of its result.
     *
     * @param array $outcomes
     * @return string html
     */
    private function render_preview(array $outcomes): string {
        global $OUTPUT;

        $counts = [
            mapping_import::RESULT_CREATED => 0,
            mapping_import::RESULT_SKIPPED => 0,
            mapping_import::RESULT_ERROR => 0,
        ];
        foreach ($outcomes as $outcome) {
            $counts[$outcome->result]++;
        }

        $message = get_string('import_counts', 'auth_musaml', (object)$counts);
        $style = $counts[mapping_import::RESULT_ERROR] ? 'error' : 'info';
        $html = $OUTPUT->notification($message, $style, false);

        $labels = [
            mapping_import::RESULT_CREATED => get_string('import_result_created', 'auth_musaml'),
            mapping_import::RESULT_SKIPPED => get_string('import_result_skipped', 'auth_musaml'),
            mapping_import::RESULT_ERROR => get_string('import_result_error', 'auth_musaml'),
        ];
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable mb-0';
        $table->head = [
            get_string('user_mapping_guid', 'auth_musaml'),
            get_string('username'),
            get_string('status'),
            get_string('description'),
        ];

        // Long imports are not read row by row, the counts above say the rest.
        $shown = array_slice($outcomes, 0, mapping_import::PREVIEW_ROWS);
        foreach ($shown as $outcome) {
            $class = $outcome->result === mapping_import::RESULT_ERROR ? 'text-danger' : '';
            $table->data[] = [
                s($outcome->guid),
                s($outcome->username),
                \core\output\html_writer::span($labels[$outcome->result], $class),
                s($outcome->message),
            ];
        }
        $html .= \core\output\html_writer::table($table);
        if (count($outcomes) > count($shown)) {
            $more = count($outcomes) - count($shown);
            $html .= \core\output\html_writer::div(get_string('import_more_rows', 'auth_musaml', $more), 'text-muted');
        }

        return $html;
    }
}
