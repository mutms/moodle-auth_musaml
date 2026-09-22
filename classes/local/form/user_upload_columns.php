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
 * Mapping import, stage two: what is in each column.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_upload_columns extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $idp = $this->_customdata['idp'];
        $csvdata = $this->_customdata['csvdata'];
        $draftid = $this->_customdata['draftid'];
        $rows = $csvdata->rows;
        $first = $rows[0];

        // The form action carries no query string, so the page needs the id back.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $idp->id);

        // Pass the original draftitemid through.
        $mform->addElement('hidden', 'sourcefile');
        $mform->setType('sourcefile', PARAM_INT);
        $mform->setConstant('sourcefile', $draftid);

        $mform->addElement('static', 'info', '', get_string('import_columns_info', 'auth_musaml', count($rows)));
        $mform->addElement('static', 'datapreview', '', $this->render_data($rows));

        $mform->addElement('advcheckbox', 'headers', get_string('import_columns_headers', 'auth_musaml'));
        $mform->setDefault('headers', (int)mapping_import::guess_headers($first));

        $menu = mapping_import::get_column_menu();
        $guesses = mapping_import::guess_columns($first);
        foreach (array_keys($first) as $index) {
            $label = get_string('import_column_number', 'auth_musaml', $index + 1);
            $mform->addElement('select', 'column_' . $index, $label, $menu);
            $mform->setDefault('column_' . $index, $guesses[$index] ?? mapping_import::COLUMN_IGNORE);
        }

        // Coming back to this stage shows what was chosen before.
        if ($csvdata->columns) {
            $data = ['headers' => (int)$csvdata->columns['headers']];
            foreach ($csvdata->columns['map'] as $index => $value) {
                $data['column_' . $index] = $value;
            }
            $this->set_data($data);
        }

        $this->add_action_buttons(true, get_string('continue'));
    }

    /**
     * First rows of the data as they were read.
     *
     * @param array $rows
     * @return string html
     */
    private function render_data(array $rows): string {
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable mb-0';
        foreach (array_keys($rows[0]) as $index) {
            $table->head[] = get_string('import_column_number', 'auth_musaml', $index + 1);
        }

        $shown = array_slice($rows, 0, mapping_import::DATA_PREVIEW_ROWS);
        foreach ($shown as $row) {
            $table->data[] = array_map('s', $row);
        }
        $html = \core\output\html_writer::table($table);

        if (count($rows) > count($shown)) {
            $more = count($rows) - count($shown);
            $html .= \core\output\html_writer::div(get_string('import_more_rows', 'auth_musaml', $more), 'text-muted');
        }

        return $html;
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $rows = $this->_customdata['csvdata']->rows;

        $map = [];
        foreach (array_keys($rows[0]) as $index) {
            $map[$index] = $data['column_' . $index] ?? mapping_import::COLUMN_IGNORE;
        }
        $columns = ['headers' => !empty($data['headers']), 'map' => $map];
        foreach (mapping_import::check_columns($rows, $columns) as $name => $errorcode) {
            $errors[$name] = get_string($errorcode, 'auth_musaml');
        }

        return $errors;
    }
}
