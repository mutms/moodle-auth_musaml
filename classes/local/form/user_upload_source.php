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
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\filemanager;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;

/**
 * Mapping import, stage one: where the CSV data comes from.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_upload_source extends form {
    #[\Override]
    protected function definition(): void {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $this->add(new info('info', '', get_string('import_source_info', 'auth_musaml')));
        $this->add(new filemanager('sourcefile', get_string('import_file', 'auth_musaml'), 1, ['.csv', '.tsv', '.txt']));
        $this->add(new textarea('csvtext', get_string('import_text', 'auth_musaml'), ['type' => 'rawtext', 'rows' => 8]));

        $encoding = (new select('encoding', get_string('import_encoding', 'auth_musaml'), \core_text::get_encodings()))
            ->set_default('UTF-8');
        $this->add($encoding);

        $delimiters = [mapping_import::DELIMITER_AUTO => get_string('import_delimiter_auto', 'auth_musaml')]
            + \csv_import_reader::get_delimiter_list();
        $delimiter = (new select('delimiter_name', get_string('import_delimiter', 'auth_musaml'), $delimiters))
            ->set_default(mapping_import::DELIMITER_AUTO);
        $this->add($delimiter);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('continue')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * Uploaded file content or the pasted text, the file wins.
     *
     * @return string
     */
    public function get_source_content(): string {
        foreach ($this->get_element('sourcefile')->get_files() as $file) {
            return trim($file->get_content());
        }
        return trim((string)$this->get_element('csvtext')->get_value());
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $content = $this->get_source_content();
        if ($content === '') {
            $allerrors['sourcefile'][] = get_string('import_error_source', 'auth_musaml');
            return;
        }
        // Only a check, the page stores what the import needs.
        try {
            $rows = mapping_import::parse($content, $data['encoding'], $data['delimiter_name']);
        } catch (coding_exception $e) {
            $allerrors['sourcefile'][] = $e->getMessage();
            return;
        }
        if ($errorcode = mapping_import::check_rows($rows)) {
            $allerrors['sourcefile'][] = get_string($errorcode, 'auth_musaml');
        }
    }
}
