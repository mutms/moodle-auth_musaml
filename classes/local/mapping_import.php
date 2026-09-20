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
    /** @var string file area holding parsed rows between wizard stages */
    public const FILEAREA = 'import';

    /** @var string name of the stored file */
    public const FILENAME = 'data.json';

    /** @var string csv_import_reader type */
    public const CSVTYPE = 'auth_musaml_user';

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
     * Store parsed rows for the next wizard stage.
     *
     * @param int $draftid
     * @param array $rows first row holds the column headers
     */
    public static function store_data(int $draftid, array $rows): void {
        global $USER;

        $fs = get_file_storage();
        $context = \core\context\user::instance($USER->id);
        $fs->delete_area_files($context->id, 'auth_musaml', self::FILEAREA, $draftid);

        $record = [
            'contextid' => $context->id,
            'component' => 'auth_musaml',
            'filearea' => self::FILEAREA,
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => self::FILENAME,
        ];
        $content = json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $fs->create_file_from_string($record, $content);
    }

    /**
     * Parsed rows of an unfinished import.
     *
     * @param int $draftid
     * @return array|null
     */
    public static function get_data(int $draftid): ?array {
        global $USER;

        if (!$draftid) {
            return null;
        }
        $fs = get_file_storage();
        $context = \core\context\user::instance($USER->id);
        $file = $fs->get_file($context->id, 'auth_musaml', self::FILEAREA, $draftid, '/', self::FILENAME);
        if (!$file) {
            return null;
        }
        if ($file->get_timecreated() < time() - self::DATA_TTL) {
            self::delete_data($draftid);
            return null;
        }
        $rows = json_decode($file->get_content(), true);
        if (!is_array($rows) || !$rows) {
            return null;
        }
        return fix_utf8($rows);
    }

    /**
     * Forget the parsed rows.
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
        $fs->delete_area_files($context->id, 'auth_musaml', self::FILEAREA, $draftid);
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
     * @param array $rows including the header row
     * @param array $columns
     * @param stdClass $options
     * @return array list of outcomes, one per data row
     */
    public static function check(stdClass $idp, array $rows, array $columns, stdClass $options): array {
        $outcomes = [];
        foreach (array_slice($rows, 1) as $row) {
            $outcomes[] = self::check_row($idp, $row, $columns, $options);
        }
        return $outcomes;
    }

    /**
     * Import the rows that passed the check.
     *
     * Nothing is written when any row failed, the admin has to resolve it or skip it.
     *
     * @param stdClass $idp
     * @param array $rows including the header row
     * @param array $columns
     * @param stdClass $options
     * @return array counts keyed by result
     */
    public static function import(stdClass $idp, array $rows, array $columns, stdClass $options): array {
        global $DB;

        $outcomes = self::check($idp, $rows, $columns, $options);
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
