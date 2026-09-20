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

use stdClass;

/**
 * User mapping helper.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mapping {
    /**
     * Mapping of one identity provider account.
     *
     * @param int $idpid
     * @param string $guid
     * @return stdClass|null
     */
    public static function fetch_by_guid(int $idpid, string $guid): ?stdClass {
        global $DB;
        $record = $DB->get_record('auth_musaml_user', ['idpid' => $idpid, 'guid' => $guid]);
        return $record ?: null;
    }

    /**
     * Mapping of one Moodle user, there is at most one.
     *
     * @param int $userid
     * @return stdClass|null
     */
    public static function fetch_by_userid(int $userid): ?stdClass {
        global $DB;
        $record = $DB->get_record('auth_musaml_user', ['userid' => $userid]);
        return $record ?: null;
    }

    /**
     * Fetch one mapping.
     *
     * @param int $id
     * @return stdClass|null
     */
    public static function fetch(int $id): ?stdClass {
        global $DB;
        $record = $DB->get_record('auth_musaml_user', ['id' => $id]);
        return $record ?: null;
    }

    /**
     * Update mapping, the mapped user cannot be changed.
     *
     * @param stdClass $data id plus guid and allowotherauth
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $record = self::fetch($data->id);
        if (!$record) {
            throw new \core\exception\coding_exception('invalid mapping id');
        }
        if (isset($data->guid)) {
            $guid = trim((string)$data->guid);
            if ($guid === '' || \core_text::strlen($guid) > 255) {
                throw new \core\exception\coding_exception('invalid guid');
            }
            $other = self::fetch_by_guid($record->idpid, $guid);
            if ($other && $other->id != $record->id) {
                throw new \core\exception\moodle_exception('error_guidmapped', 'auth_musaml');
            }
            $record->guid = $guid;
        }
        if (isset($data->allowotherauth)) {
            $record->allowotherauth = (int)(bool)$data->allowotherauth;
        }

        $DB->update_record('auth_musaml_user', $record);
        return self::fetch($record->id);
    }

    /**
     * Switch the user account to SAML authentication.
     *
     * @param int $userid
     */
    public static function set_user_auth(int $userid): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if ($user->auth === 'musaml') {
            return;
        }
        $user->auth = 'musaml';
        \core\user::update_user($user, false, false);
        \core\event\user_updated::create_from_userid($user->id)->trigger();
    }

    /**
     * Create mapping.
     *
     * @param stdClass $data idpid, guid, userid, optional allowotherauth and automapped
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $record = new stdClass();
        $record->idpid = (int)$data->idpid;
        $record->guid = trim((string)$data->guid);
        $record->userid = (int)$data->userid;
        $record->allowotherauth = (int)(bool)($data->allowotherauth ?? 0);
        $record->automapped = (int)(bool)($data->automapped ?? 0);
        $record->timecreated = time();

        if ($record->guid === '' || \core_text::strlen($record->guid) > 255) {
            throw new \core\exception\coding_exception('invalid guid');
        }
        if (!$DB->record_exists('auth_musaml_idp', ['id' => $record->idpid])) {
            throw new \core\exception\coding_exception('invalid idpid');
        }
        if (!$DB->record_exists('user', ['id' => $record->userid, 'deleted' => 0])) {
            throw new \core\exception\coding_exception('invalid userid');
        }
        if (self::fetch_by_userid($record->userid)) {
            throw new \core\exception\moodle_exception('error_usermapped', 'auth_musaml');
        }
        if (self::fetch_by_guid($record->idpid, $record->guid)) {
            throw new \core\exception\moodle_exception('error_guidmapped', 'auth_musaml');
        }

        $record->id = $DB->insert_record('auth_musaml_user', $record);
        $mapping = $DB->get_record('auth_musaml_user', ['id' => $record->id], '*', MUST_EXIST);

        \auth_musaml\event\user_mapping_created::create_from_mapping($mapping)->trigger();

        return $mapping;
    }

    /**
     * Delete one mapping.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB;

        $mapping = self::fetch($id);
        if (!$mapping) {
            return;
        }
        $DB->delete_records('auth_musaml_user', ['id' => $id]);

        \auth_musaml\event\user_mapping_deleted::create_from_mapping($mapping)->trigger();
    }

    /**
     * Number of users mapped to one IDP.
     *
     * @param int $idpid
     * @return int
     */
    public static function count_for_idp(int $idpid): int {
        global $DB;
        return $DB->count_records('auth_musaml_user', ['idpid' => $idpid]);
    }

    /**
     * Mappings of one IDP with basic user details.
     *
     * @param int $idpid
     * @return stdClass[]
     */
    public static function get_for_idp(int $idpid): array {
        global $DB;
        $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $sql = "SELECT m.*, u.username, u.email, u.auth, u.suspended, u.deleted, $userfields
                  FROM {auth_musaml_user} m
                  JOIN {user} u ON u.id = m.userid
                 WHERE m.idpid = :idpid
              ORDER BY u.lastname ASC, u.firstname ASC, m.id ASC";
        return $DB->get_records_sql($sql, ['idpid' => $idpid]);
    }

    /**
     * Delete all mappings of one IDP.
     *
     * @param int $idpid
     */
    public static function delete_for_idp(int $idpid): void {
        global $DB;

        foreach ($DB->get_records('auth_musaml_user', ['idpid' => $idpid], '', 'id') as $mapping) {
            self::delete($mapping->id);
        }
    }
}
