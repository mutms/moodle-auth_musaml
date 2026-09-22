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

namespace auth_musaml\local;

use core\exception\coding_exception;
use stdClass;

/**
 * Bulk import of user mappings from CSV data.
 *
 * The wizard parses the uploaded or pasted text once and keeps the rows as a JSON
 * file in the user file area, so later stages only carry the draft item id.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mapping_import {
    /** @var string name of the stored file */
    public const FILENAME = 'csvdata.json';

    /** @var string csv_import_reader type */
    public const CSVTYPE = 'auth_musaml_user';

    /** @var int rows of the preview table */
    public const PREVIEW_ROWS = 10;

    /** @var int stored data is ignored after this many seconds */
    public const DATA_TTL = DAYSECS;

    /** @var string column is not used */
    public const COLUMN_IGNORE = '';

    /** @var string separator value that means the plugin works it out */
    public const DELIMITER_AUTO = 'auto';

    /** @var string column holds the identity provider account id */
    public const COLUMN_GUID = 'guid';

    /** @var string row was imported */
    public const RESULT_CREATED = 'created';

    /** @var string row was skipped */
    public const RESULT_SKIPPED = 'skipped';

    /** @var string row cannot be imported */
    public const RESULT_ERROR = 'error';

    /**
     * Columns that may identify the user.
     *
     * @return array column value => label
     */
    public static function get_column_menu(): array {
        return [
            self::COLUMN_IGNORE => get_string('import_column_ignore', 'auth_musaml'),
            self::COLUMN_GUID => get_string('user_mapping_guid', 'auth_musaml'),
            'username' => get_string('username'),
            'email' => get_string('email'),
            'idnumber' => get_string('idnumber'),
        ];
    }

    /**
     * Skip options, each one turns a class of problem into a skipped row.
     *
     * @return array name => label
     */
    public static function get_skip_menu(): array {
        return [
            'skipmissing' => get_string('import_skip_missing', 'auth_musaml'),
            'skipambiguous' => get_string('import_skip_ambiguous', 'auth_musaml'),
            'skipunusable' => get_string('import_skip_unusable', 'auth_musaml'),
            'skipmapped' => get_string('import_skip_mapped', 'auth_musaml'),
            'skipguidused' => get_string('import_skip_guidused', 'auth_musaml'),
            'skipinvalid' => get_string('import_skip_invalid', 'auth_musaml'),
        ];
    }

    /**
     * State of an unfinished import.
     *
     * The shape never changes, a missing or unusable section comes back empty, so the
     * callers only ask which stage is due.
     *
     * @param int $draftid
     * @return stdClass keys: rows, columns, options
     */
    public static function get_data(int $draftid): stdClass {
        global $USER;

        $csvdata = (object)['rows' => [], 'columns' => [], 'options' => []];
        if (!$draftid) {
            return $csvdata;
        }

        $fs = get_file_storage();
        $context = \core\context\user::instance($USER->id);
        $file = $fs->get_file($context->id, 'user', 'draft', $draftid, '/', self::FILENAME);
        if (!$file) {
            return $csvdata;
        }
        if ($file->get_timecreated() < time() - self::DATA_TTL) {
            self::delete_data($draftid);
            return $csvdata;
        }

        $stored = json_decode($file->get_content(), true);
        if (!is_array($stored)) {
            return $csvdata;
        }
        return self::validate($stored);
    }

    /**
     * Keep only the sections that can be used, a document may also come from an upload.
     *
     * @param array $stored decoded document
     * @return stdClass keys: rows, columns, options
     */
    public static function validate(array $stored): stdClass {
        $csvdata = (object)['rows' => [], 'columns' => [], 'options' => []];

        $rows = $stored['rows'] ?? null;
        if (is_array($rows) && !self::check_rows($rows)) {
            $csvdata->rows = fix_utf8(array_values(array_map('array_values', $rows)));
        }
        if (!$csvdata->rows) {
            return $csvdata;
        }

        $columns = $stored['columns'] ?? null;
        if (is_array($columns)) {
            $clean = [];
            foreach ($csvdata->rows[0] as $index => $unused) {
                $value = $columns[$index] ?? null;
                $clean[$index] = is_string($value) ? $value : self::COLUMN_IGNORE;
            }
            if (!self::check_columns($csvdata->rows[0], $clean)) {
                $csvdata->columns = $clean;
            }
        }

        $options = $stored['options'] ?? null;
        if (is_array($options)) {
            $names = self::get_option_names();
            $clean = [];
            foreach ($names as $name) {
                if (isset($options[$name])) {
                    $clean[$name] = (int)(bool)$options[$name];
                }
            }
            if (isset($options['preview']) && is_bool($options['preview'])) {
                // False means the options were confirmed and the import may run.
                $clean['preview'] = $options['preview'];
            }
            $csvdata->options = $clean;
        }

        return $csvdata;
    }

    /**
     * Problem with the data itself, null when it can be used.
     *
     * The form and the stored document are checked by the same rules, a document may
     * come from an upload and must be a table like any parsed CSV.
     *
     * @param array $rows first row holds the column headers
     * @return string|null string identifier of the problem
     */
    public static function check_rows(array $rows): ?string {
        if (count($rows) < 2) {
            return 'import_error_empty';
        }

        $width = null;
        foreach ($rows as $row) {
            if (!is_array($row) || !$row) {
                return 'import_error_rowsize';
            }
            foreach ($row as $value) {
                if (!is_scalar($value)) {
                    return 'import_error_rowsize';
                }
            }
            if ($width !== null && count($row) !== $width) {
                return 'import_error_rowsize';
            }
            $width = count($row);
        }

        return null;
    }

    /**
     * Problems with the meaning given to the columns, empty when they can be used.
     *
     * The form and the stored document are checked by the same rules, a document may
     * come from an upload and must meet what the form would demand.
     *
     * @param array $headers first row of the data
     * @param array $columns column index => column value
     * @return array string identifier of the problem, keyed by column index
     */
    public static function check_columns(array $headers, array $columns): array {
        $errors = [];
        $menu = self::get_column_menu();
        $used = [];

        foreach (array_keys($headers) as $index) {
            $value = $columns[$index] ?? self::COLUMN_IGNORE;
            if (!is_string($value) || !array_key_exists($value, $menu)) {
                $errors[$index] = 'import_error_columnunknown';
                continue;
            }
            if ($value === self::COLUMN_IGNORE) {
                continue;
            }
            if (isset($used[$value])) {
                $errors[$index] = 'import_error_columntwice';
            }
            $used[$value] = true;
        }

        if (!isset($used[self::COLUMN_GUID])) {
            $errors[0] = 'import_error_noguid';
        }
        if (count($used) < 2) {
            // One column says who the identity provider means, another who we mean.
            $errors[0] = 'import_error_nouser';
        }

        return $errors;
    }

    /**
     * Names of all import options, the options section holds each of them.
     *
     * @return array
     */
    public static function get_option_names(): array {
        return array_merge(array_keys(self::get_skip_menu()), ['allowotherauth', 'setauth']);
    }

    /**
     * Is the wizard still waiting for the data itself?
     *
     * Each stage judges only its own data, data that cannot be used keeps the
     * administrator on the stage that produces it.
     *
     * @param stdClass $csvdata
     * @return bool
     */
    public static function is_source_stage(stdClass $csvdata): bool {
        return self::check_rows($csvdata->rows) !== null;
    }

    /**
     * Is the wizard waiting for the meaning of the columns?
     *
     * Only for data that passed the source stage.
     *
     * @param stdClass $csvdata
     * @return bool
     */
    public static function is_columns_stage(stdClass $csvdata): bool {
        return !$csvdata->columns || (bool)self::check_columns($csvdata->rows[0], $csvdata->columns);
    }

    /**
     * Is the wizard waiting for the import options?
     *
     * Options of an uploaded document are only defaults of the form, the wizard moves on
     * when they were confirmed here, which is what the preview flag records.
     * Only for data that passed the columns stage.
     *
     * @param stdClass $csvdata
     * @return bool
     */
    public static function is_options_stage(stdClass $csvdata): bool {
        if (($csvdata->options['preview'] ?? true) !== false) {
            return true;
        }
        foreach (self::get_option_names() as $name) {
            if (!array_key_exists($name, $csvdata->options)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Store the data of the source stage, pasted, uploaded CSV or a whole document.
     *
     * @param stdClass $formdata data of the source form
     * @return stdClass keys: rows, columns, options
     */
    public static function save_source(stdClass $formdata): stdClass {
        $draftid = (int)$formdata->sourcefile;
        $content = self::get_source_content($formdata);

        $stored = json_decode($content, true);
        if (!is_array($stored) || !isset($stored['rows'])) {
            // Not a document of an earlier import, so it is the CSV text itself.
            $stored = ['rows' => self::parse($content, $formdata->encoding, $formdata->delimiter_name)];
        }

        return self::store_data($draftid, self::validate($stored));
    }

    /**
     * Store the import options, the wizard has nothing left to ask afterwards.
     *
     * @param stdClass $csvdata
     * @param stdClass $formdata data of the options form
     * @return stdClass keys: rows, columns, options
     */
    public static function save_options(stdClass $csvdata, stdClass $formdata): stdClass {
        $options = [];
        foreach (self::get_option_names() as $name) {
            $options[$name] = (int)(bool)($formdata->$name ?? 0);
        }
        $options['preview'] = false;
        $csvdata->options = $options;

        return self::store_data((int)$formdata->sourcefile, $csvdata);
    }

    /**
     * Store the meaning of each column.
     *
     * @param stdClass $csvdata
     * @param stdClass $formdata data of the columns form
     * @return stdClass keys: rows, columns, options
     */
    public static function save_columns(stdClass $csvdata, stdClass $formdata): stdClass {
        $columns = [];
        foreach ($csvdata->rows[0] as $index => $unused) {
            $columns[$index] = $formdata->{'column_' . $index} ?? self::COLUMN_IGNORE;
        }
        $csvdata->columns = $columns;

        return self::store_data((int)$formdata->sourcefile, $csvdata);
    }

    /**
     * CSV text or uploaded file content of the source form.
     *
     * @param stdClass $formdata data of the source form
     * @return string
     */
    public static function get_source_content(stdClass $formdata): string {
        global $USER;

        $draftid = (int)$formdata->sourcefile;
        if ($draftid) {
            $fs = get_file_storage();
            $context = \core\context\user::instance($USER->id);
            foreach ($fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false) as $file) {
                if ($file->get_filename() !== self::FILENAME) {
                    return trim($file->get_content());
                }
            }
        }

        return trim((string)($formdata->csvtext ?? ''));
    }

    /**
     * Write the document, the uploaded file is replaced by it.
     *
     * The draft area of the upload form is used, so an abandoned import is swept by the
     * core draft cleanup.
     *
     * @param int $draftid
     * @param stdClass $csvdata
     * @return stdClass the stored document
     */
    private static function store_data(int $draftid, stdClass $csvdata): stdClass {
        global $USER;

        $fs = get_file_storage();
        $context = \core\context\user::instance($USER->id);
        $fs->delete_area_files($context->id, 'user', 'draft', $draftid);

        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => self::FILENAME,
        ];
        $content = json_encode($csvdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $fs->create_file_from_string($record, $content);

        return $csvdata;
    }

    /**
     * Forget an unfinished import.
     *
     * @param int $draftid
     */
    public static function delete_data(int $draftid): void {
        global $USER;

        if (!$draftid) {
            return;
        }
        $fs = get_file_storage();
        $context = \core\context\user::instance($USER->id);
        $fs->delete_area_files($context->id, 'user', 'draft', $draftid);
    }

    /**
     * Parse CSV text into rows.
     *
     * @param string $content
     * @param string $encoding
     * @param string $delimitername separator name, or "auto" to detect it
     * @return array first row holds the column headers
     * @throws coding_exception when the text cannot be parsed
     */
    public static function parse(string $content, string $encoding, string $delimitername): array {
        if ($delimitername !== self::DELIMITER_AUTO) {
            return self::parse_with($content, $encoding, $delimitername);
        }

        // A spreadsheet copies and saves columns separated by tabs, and none of these
        // characters appears inside a user id or an email address, so the first
        // separator that yields more than one column is the right one.
        $rows = [];
        foreach (['comma', 'tab', 'semicolon', 'colon'] as $candidate) {
            $rows = self::parse_with($content, $encoding, $candidate);
            if (count(reset($rows)) > 1) {
                return $rows;
            }
        }

        return $rows;
    }

    /**
     * Parse the text with one given separator.
     *
     * @param string $content
     * @param string $encoding
     * @param string $delimitername
     * @return array first row holds the column headers
     * @throws coding_exception when the text cannot be parsed
     */
    private static function parse_with(string $content, string $encoding, string $delimitername): array {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $content = trim($content);
        if ($content === '') {
            throw new coding_exception(get_string('import_error_empty', 'auth_musaml'));
        }

        $iid = \csv_import_reader::get_new_iid(self::CSVTYPE);
        $cir = new \csv_import_reader($iid, self::CSVTYPE);
        $readcount = $cir->load_csv_content($content, $encoding, $delimitername);
        $columns = $cir->get_columns();
        $error = $cir->get_error();
        if ($error !== null) {
            $cir->cleanup(true);
            throw new coding_exception($error);
        }
        if (!$readcount || !$columns) {
            $cir->cleanup(true);
            throw new coding_exception(get_string('import_error_empty', 'auth_musaml'));
        }

        $rows = [array_map('trim', $columns)];
        $cir->init();
        while ($line = $cir->next()) {
            $rows[] = array_map('trim', $line);
        }
        $cir->close();
        $cir->cleanup(true);

        return $rows;
    }

    /**
     * Check one row and work out what would happen to it.
     *
     * @param stdClass $idp
     * @param array $row raw values
     * @param array $columns column index => column value
     * @param stdClass $options skip flags plus allowotherauth and setauth
     * @return stdClass keys: result, message, guid, userid, username
     */
    public static function check_row(stdClass $idp, array $row, array $columns, stdClass $options): stdClass {
        global $DB, $CFG;

        $outcome = (object)[
            'result' => self::RESULT_ERROR,
            'message' => '',
            'guid' => '',
            'userid' => null,
            'username' => '',
        ];

        $values = [];
        foreach ($columns as $index => $column) {
            if ($column === self::COLUMN_IGNORE) {
                continue;
            }
            $values[$column] = isset($row[$index]) ? trim((string)$row[$index]) : '';
        }

        $guid = $values[self::COLUMN_GUID] ?? '';
        unset($values[self::COLUMN_GUID]);
        $outcome->guid = $guid;

        $hasidentifier = (bool)array_filter($values, fn($v) => $v !== '');
        if ($guid === '' || \core_text::strlen($guid) > 255 || !$hasidentifier) {
            return self::outcome($outcome, !empty($options->skipinvalid), 'import_error_invalid');
        }

        // Every given user column must point at the same single user.
        $found = null;
        foreach ($values as $field => $value) {
            if ($value === '') {
                continue;
            }
            $select = $DB->sql_equal($field, ':value', false) . ' AND deleted = 0 AND mnethostid = :mnethostid';
            $params = ['value' => $value, 'mnethostid' => $CFG->mnet_localhost_id];
            $fields = 'id, username, auth, suspended';
            $users = $DB->get_records_select('user', $select, $params, 'id ASC', $fields, 0, 2);
            if (!$users) {
                return self::outcome($outcome, !empty($options->skipmissing), 'import_error_missing', $value);
            }
            if (count($users) > 1) {
                return self::outcome($outcome, !empty($options->skipambiguous), 'import_error_ambiguous', $value);
            }
            $user = reset($users);
            if ($found && $found->id != $user->id) {
                return self::outcome($outcome, !empty($options->skipambiguous), 'import_error_ambiguous', $value);
            }
            $found = $user;
        }

        $outcome->userid = $found->id;
        $outcome->username = $found->username;

        if ($found->suspended) {
            return self::outcome($outcome, !empty($options->skipunusable), 'import_error_suspended', $found->username);
        }
        if ($found->auth !== 'musaml' && empty($options->setauth) && empty($options->allowotherauth)) {
            return self::outcome($outcome, !empty($options->skipunusable), 'import_error_otherauth', $found->auth);
        }

        $existing = mapping::fetch_by_userid($found->id);
        if ($existing) {
            $same = $existing->idpid == $idp->id && $existing->guid === $guid;
            $code = $same ? 'import_error_mappedsame' : 'import_error_mapped';
            return self::outcome($outcome, !empty($options->skipmapped), $code, $found->username);
        }

        $guidowner = mapping::fetch_by_guid($idp->id, $guid);
        if ($guidowner) {
            return self::outcome($outcome, !empty($options->skipguidused), 'import_error_guidused', $guid);
        }

        $outcome->result = self::RESULT_CREATED;
        return $outcome;
    }

    /**
     * Check all rows without changing anything.
     *
     * @param stdClass $idp
     * @param stdClass $csvdata rows and columns of the import
     * @param stdClass $options
     * @return array list of outcomes, one per data row
     */
    public static function check(stdClass $idp, stdClass $csvdata, stdClass $options): array {
        $outcomes = [];
        foreach (array_slice($csvdata->rows, 1) as $row) {
            $outcomes[] = self::check_row($idp, $row, $csvdata->columns, $options);
        }
        return $outcomes;
    }

    /**
     * Import the rows that passed the check.
     *
     * Nothing is written when any row failed, the admin has to resolve it or skip it.
     *
     * @param stdClass $idp
     * @param stdClass $csvdata rows and columns of the import
     * @param stdClass $options
     * @return array counts keyed by result
     */
    public static function import(stdClass $idp, stdClass $csvdata, stdClass $options): array {
        global $DB;

        $outcomes = self::check($idp, $csvdata, $options);
        $counts = [self::RESULT_CREATED => 0, self::RESULT_SKIPPED => 0, self::RESULT_ERROR => 0];
        foreach ($outcomes as $outcome) {
            $counts[$outcome->result]++;
        }
        if ($counts[self::RESULT_ERROR]) {
            return $counts;
        }

        $trans = $DB->start_delegated_transaction();
        foreach ($outcomes as $outcome) {
            if ($outcome->result !== self::RESULT_CREATED) {
                continue;
            }
            mapping::create((object)[
                'idpid' => $idp->id,
                'userid' => $outcome->userid,
                'guid' => $outcome->guid,
                'allowotherauth' => !empty($options->allowotherauth),
            ]);
            if (!empty($options->setauth)) {
                mapping::set_user_auth($outcome->userid);
            }
        }
        $trans->allow_commit();

        return $counts;
    }

    /**
     * Mark the outcome as skipped or failed.
     *
     * @param stdClass $outcome
     * @param bool $skip
     * @param string $errorcode
     * @param string|null $a
     * @return stdClass
     */
    private static function outcome(stdClass $outcome, bool $skip, string $errorcode, ?string $a = null): stdClass {
        $outcome->result = $skip ? self::RESULT_SKIPPED : self::RESULT_ERROR;
        $outcome->message = get_string($errorcode, 'auth_musaml', $a);
        return $outcome;
    }

    /**
     * Guess the meaning of each column from its header.
     *
     * @param array $headers
     * @return array column index => column value
     */
    public static function guess_columns(array $headers): array {
        $guesses = [];
        foreach ($headers as $index => $header) {
            $header = \core_text::strtolower(trim((string)$header));
            $header = preg_replace('/[^a-z0-9]/', '', $header);
            $guess = match ($header) {
                'guid', 'idpuserid', 'userid', 'objectid', 'nameid', 'subject' => self::COLUMN_GUID,
                'username', 'login', 'loginname' => 'username',
                'email', 'emailaddress', 'mail' => 'email',
                'idnumber', 'id' => 'idnumber',
                default => self::COLUMN_IGNORE,
            };
            $guesses[$index] = $guess;
        }
        return $guesses;
    }
}
