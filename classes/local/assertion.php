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

/**
 * Replay protection for received assertions.
 *
 * Assertion ids are stored until they expire, a duplicate insert means the
 * response was replayed. Expired rows are removed on every insert, there is no task.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assertion {
    /** @var int allowed clock skew in seconds, added to NotOnOrAfter */
    public const CLOCK_SKEW = 60;

    /** @var int retention when the assertion has no NotOnOrAfter */
    public const DEFAULT_TTL = DAYSECS;

    /**
     * Delete the stored assertion ids of one IDP.
     *
     * @param int $idpid
     */
    public static function delete_for_idp(int $idpid): void {
        global $DB;
        $DB->delete_records('auth_musaml_assertion', ['idpid' => $idpid]);
    }

    /**
     * Remember the assertion id, false when it was seen before.
     *
     * @param int $idpid
     * @param string $assertionid
     * @param int|null $notonorafter
     * @return bool true if new, false if replayed
     */
    public static function register(int $idpid, string $assertionid, ?int $notonorafter): bool {
        global $DB;

        $assertionid = trim($assertionid);
        if ($assertionid === '' || \core_text::strlen($assertionid) > 255) {
            return false;
        }

        $DB->delete_records_select('auth_musaml_assertion', 'timeexpires < :now', ['now' => time()]);

        $record = (object)[
            'idpid' => $idpid,
            'assertionid' => $assertionid,
            'timeexpires' => ($notonorafter ?: time() + self::DEFAULT_TTL) + self::CLOCK_SKEW,
        ];
        try {
            $DB->insert_record('auth_musaml_assertion', $record);
        } catch (\dml_write_exception $e) {
            if ($DB->record_exists('auth_musaml_assertion', ['assertionid' => $assertionid])) {
                return false;
            }
            throw $e;
        }
        return true;
    }
}
