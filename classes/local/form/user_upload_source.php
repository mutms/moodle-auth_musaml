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
use core\exception\coding_exception;

/**
 * Mapping import, stage one: where the CSV data comes from.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_upload_source extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $mform = $this->_form;
        $idp = $this->_customdata['idp'];

        // The form action carries no query string, so the page needs the id back.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $idp->id);

        $mform->addElement('static', 'info', '', get_string('import_source_info', 'auth_musaml'));

        $filetypes = ['accepted_types' => ['.csv', '.txt', '.json']];
        $mform->addElement('filepicker', 'sourcefile', get_string('import_file', 'auth_musaml'), null, $filetypes);

        $textareaoptions = ['rows' => 8, 'cols' => 70, 'class' => 'text-monospace'];
        $mform->addElement('textarea', 'csvtext', get_string('import_text', 'auth_musaml'), $textareaoptions);
        $mform->setType('csvtext', PARAM_RAW);

        $mform->addElement('select', 'encoding', get_string('import_encoding', 'auth_musaml'), \core_text::get_encodings());
        $mform->setDefault('encoding', 'UTF-8');

        $delimiters = [mapping_import::DELIMITER_AUTO => get_string('import_delimiter_auto', 'auth_musaml')]
            + \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('import_delimiter', 'auth_musaml'), $delimiters);
        $mform->setDefault('delimiter_name', mapping_import::DELIMITER_AUTO);

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $content = mapping_import::get_source_content((object)$data);
        if ($content === '') {
            $errors['sourcefile'] = get_string('import_error_source', 'auth_musaml');
            return $errors;
        }

        // Only a check, the page stores what the import needs.
        $stored = json_decode($content, true);
        if (is_array($stored) && isset($stored['rows'])) {
            $rows = is_array($stored['rows']) ? $stored['rows'] : [];
        } else {
            try {
                $rows = mapping_import::parse($content, $data['encoding'], $data['delimiter_name']);
            } catch (coding_exception $e) {
                $errors['sourcefile'] = $e->getMessage();
                return $errors;
            }
        }

        if ($errorcode = mapping_import::check_rows($rows)) {
            $errors['sourcefile'] = get_string($errorcode, 'auth_musaml');
        }

        return $errors;
    }
}
