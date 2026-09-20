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
        $rows = $this->_customdata['rows'];
        $draftid = $this->_customdata['draftid'];
        $headers = $rows[0];

        $mform->addElement('hidden', 'idpid');
        $mform->setType('idpid', PARAM_INT);
        $mform->setConstant('idpid', $idp->id);

        $mform->addElement('hidden', 'draftid');
        $mform->setType('draftid', PARAM_INT);
        $mform->setConstant('draftid', $draftid);

        $count = count($rows) - 1;
        $mform->addElement('static', 'info', '', get_string('import_columns_info', 'auth_musaml', $count));

        $menu = mapping_import::get_column_menu();
        $guesses = mapping_import::guess_columns($headers);
        $sample = $rows[1] ?? [];
        foreach ($headers as $index => $header) {
            $label = s((string)$header);
            if (isset($sample[$index]) && trim((string)$sample[$index]) !== '') {
                $label .= ' ' . \core\output\html_writer::span(s((string)$sample[$index]), 'text-muted small');
            }
            $mform->addElement('select', 'column_' . $index, $label, $menu);
            $mform->setDefault('column_' . $index, $guesses[$index] ?? mapping_import::COLUMN_IGNORE);
        }

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $rows = $this->_customdata['rows'];
        $headers = $rows[0];

        $used = [];
        foreach (array_keys($headers) as $index) {
            $value = $data['column_' . $index] ?? mapping_import::COLUMN_IGNORE;
            if ($value === mapping_import::COLUMN_IGNORE) {
                continue;
            }
            if (isset($used[$value])) {
                $errors['column_' . $index] = get_string('import_error_columntwice', 'auth_musaml');
            }
            $used[$value] = true;
        }

        if (!isset($used[mapping_import::COLUMN_GUID])) {
            $errors['column_0'] = get_string('import_error_noguid', 'auth_musaml');
        }
        if (count($used) < 2) {
            $errors['column_0'] = get_string('import_error_nouser', 'auth_musaml');
        }
        return $errors;
    }
}
