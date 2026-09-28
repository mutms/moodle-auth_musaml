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
 * The wizard parses the uploaded or pasted text once, the page keeps the rows, the column
 * meanings and the options in the muform wizard state; this class only validates and
 * transforms that data and runs the import.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mapping_import {
    /** @var string csv_import_reader type */
    public const CSVTYPE = 'auth_musaml_user';

    /** @var int rows of the dry run preview table */
    public const PREVIEW_ROWS = 10;

    /** @var int rows of the data preview shown with the column meanings */
    public const DATA_PREVIEW_ROWS = 4;

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
     * Keep only the sections that can be used.
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
            $map = [];
            foreach ($csvdata->rows[0] as $index => $unused) {
                $value = $columns['map'][$index] ?? null;
                $map[$index] = is_string($value) ? $value : self::COLUMN_IGNORE;
            }
            $clean = ['headers' => $columns['headers'] ?? null, 'map' => $map];
            if (!self::check_columns($csvdata->rows, $clean)) {
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
        if (count($rows) < 1) {
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
     * Problems with the columns section, empty when it can be used.
     *
     * The form and the stored document are checked by the same rules, a document may
     * come from an upload and must meet what the form would demand.
     *
     * @param array $rows all rows of the data
     * @param array $columns section holding the headers flag and the column map
     * @return array string identifier of the problem, keyed by the name of the form element
     */
    public static function check_columns(array $rows, array $columns): array {
        $errors = [];

        $headers = $columns['headers'] ?? null;
        if (!is_bool($headers)) {
            $errors['headers'] = 'import_error_headers';
        } else if ($headers && count($rows) < 2) {
            $errors['headers'] = 'import_error_nodatarows';
        }

        $map = $columns['map'] ?? null;
        if (!is_array($map)) {
            $errors['column_0'] = 'import_error_columnunknown';
            return $errors;
        }

        $menu = self::get_column_menu();
        $used = [];
        foreach (array_keys($rows[0] ?? []) as $index) {
            $value = $map[$index] ?? self::COLUMN_IGNORE;
            if (!is_string($value) || !array_key_exists($value, $menu)) {
                $errors['column_' . $index] = 'import_error_columnunknown';
                continue;
            }
            if ($value === self::COLUMN_IGNORE) {
                continue;
            }
            if (isset($used[$value])) {
                $errors['column_' . $index] = 'import_error_columntwice';
            }
            $used[$value] = true;
        }

        if (!isset($used[self::COLUMN_GUID])) {
            $errors['column_0'] = 'import_error_noguid';
        }
        if (count($used) < 2) {
            // One column says who the identity provider means, another who we mean.
            $errors['column_0'] = 'import_error_nouser';
        }

        return $errors;
    }

    /**
     * Rows that hold data, the column names are not one of them.
     *
     * Only for data that passed the columns stage.
     *
     * @param stdClass $csvdata
     * @return array
     */
    public static function get_data_rows(stdClass $csvdata): array {
        return array_slice($csvdata->rows, empty($csvdata->columns['headers']) ? 0 : 1);
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
        return !$csvdata->columns || (bool)self::check_columns($csvdata->rows, $csvdata->columns);
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
     * Data of the source stage, pasted or uploaded CSV, the later stages start over.
     *
     * @param string $content CSV text
     * @param string $encoding
     * @param string $delimitername separator name, or "auto" to detect it
     * @return stdClass keys: rows, columns, options
     */
    public static function save_source(string $content, string $encoding, string $delimitername): stdClass {
        $rows = self::parse($content, $encoding, $delimitername);
        return self::validate(['rows' => $rows]);
    }

    /**
     * Add the meaning of each column, the options have to be confirmed again.
     *
     * @param stdClass $csvdata
     * @param stdClass $formdata data of the columns form
     * @return stdClass keys: rows, columns, options
     */
    public static function save_columns(stdClass $csvdata, stdClass $formdata): stdClass {
        $map = [];
        foreach ($csvdata->rows[0] as $index => $unused) {
            $map[$index] = $formdata->{'column_' . $index} ?? self::COLUMN_IGNORE;
        }
        $csvdata->columns = ['headers' => !empty($formdata->headers), 'map' => $map];
        if ($csvdata->options) {
            // The chosen options stay as defaults, the dry run has to be seen again.
            $csvdata->options['preview'] = true;
        }
        return $csvdata;
    }

    /**
     * Add the confirmed import options, the wizard has nothing left to ask afterwards.
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
        return $csvdata;
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
        foreach (self::get_data_rows($csvdata) as $row) {
            $outcomes[] = self::check_row($idp, $row, $csvdata->columns['map'], $options);
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
     * Does the first row look like column names rather than data?
     *
     * Names of the database fields and of the identity provider account id are what an
     * export of an earlier import holds, so such a file configures itself.
     *
     * @param array $row first row of the data
     * @return bool
     */
    public static function guess_headers(array $row): bool {
        $guesses = self::guess_columns($row);
        if (!in_array(self::COLUMN_GUID, $guesses, true)) {
            return false;
        }
        $named = array_filter($guesses, fn($guess) => $guess !== self::COLUMN_IGNORE);

        return count($named) > 1;
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
