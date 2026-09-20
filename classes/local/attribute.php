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
 * User attribute mapping helper.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attribute {
    /** @var int never copy the value */
    public const SYNC_NONE = 0;

    /** @var int copy the value when the user account is created */
    public const SYNC_ONCREATE = 1;

    /** @var int copy the value at every login */
    public const SYNC_ONLOGIN = 2;

    /**
     * Sync option menu.
     *
     * @return array
     */
    public static function get_sync_menu(): array {
        return [
            self::SYNC_NONE => get_string('attribute_sync_none', 'auth_musaml'),
            self::SYNC_ONCREATE => get_string('attribute_sync_oncreate', 'auth_musaml'),
            self::SYNC_ONLOGIN => get_string('attribute_sync_onlogin', 'auth_musaml'),
        ];
    }

    /**
     * Create attribute mapping.
     *
     * @param stdClass $data idpid, idpattr, userfield, sync, lock, usermapping
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $record = new stdClass();
        $record->idpid = (int)$data->idpid;
        $record->idpattr = trim((string)$data->idpattr);
        $record->userfield = trim((string)$data->userfield);
        $record->sync = (int)($data->sync ?? self::SYNC_NONE);
        $record->usermapping = (int)(bool)($data->usermapping ?? 0);

        if ($record->idpattr === '' || $record->userfield === '') {
            throw new coding_exception('idpattr and userfield are required');
        }
        if (!in_array($record->sync, [self::SYNC_NONE, self::SYNC_ONCREATE, self::SYNC_ONLOGIN], true)) {
            throw new coding_exception('invalid sync value');
        }

        $record->id = $DB->insert_record('auth_musaml_attribute', $record);
        return $DB->get_record('auth_musaml_attribute', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Fetch one attribute mapping.
     *
     * @param int $id
     * @return stdClass|null
     */
    public static function fetch(int $id): ?stdClass {
        global $DB;
        $record = $DB->get_record('auth_musaml_attribute', ['id' => $id]);
        return $record ?: null;
    }

    /**
     * Update attribute mapping.
     *
     * @param stdClass $data id plus any of idpattr, userfield, sync, usermapping
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $record = self::fetch($data->id);
        if (!$record) {
            throw new coding_exception('invalid attribute id');
        }
        foreach (['idpattr', 'userfield'] as $field) {
            if (isset($data->$field)) {
                $record->$field = trim((string)$data->$field);
            }
        }
        if (isset($data->sync)) {
            $record->sync = (int)$data->sync;
        }
        if (isset($data->usermapping)) {
            $record->usermapping = (int)(bool)$data->usermapping;
        }
        if ($record->idpattr === '' || $record->userfield === '') {
            throw new coding_exception('idpattr and userfield are required');
        }

        $DB->update_record('auth_musaml_attribute', $record);
        return self::fetch($record->id);
    }

    /**
     * Delete one attribute mapping.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB;
        $DB->delete_records('auth_musaml_attribute', ['id' => $id]);
    }

    /**
     * Is the user field already mapped for the IDP?
     *
     * @param int $idpid
     * @param string $userfield
     * @param int $ignoreid existing mapping being edited
     * @return bool
     */
    public static function userfield_exists(int $idpid, string $userfield, int $ignoreid = 0): bool {
        global $DB;
        $select = 'idpid = :idpid AND userfield = :userfield AND id <> :ignoreid';
        $params = ['idpid' => $idpid, 'userfield' => $userfield, 'ignoreid' => $ignoreid];
        return $DB->record_exists_select('auth_musaml_attribute', $select, $params);
    }

    /**
     * Create attribute mappings from provider defaults.
     *
     * @param int $idpid
     * @param array $rows list of [idpattr, userfield, sync, lock, usermapping]
     */
    public static function create_from_defaults(int $idpid, array $rows): void {
        foreach ($rows as $row) {
            [$idpattr, $userfield, $sync, $usermapping] = $row;
            self::create((object)[
                'idpid' => $idpid,
                'idpattr' => $idpattr,
                'userfield' => $userfield,
                'sync' => $sync,
                'usermapping' => $usermapping,
            ]);
        }
    }

    /**
     * Value of one attribute for one user field, null when there is nothing usable.
     *
     * @param stdClass $attribute mapping record
     * @param array $bag attribute bag
     * @param stdClass $idp
     * @return string|null
     */
    public static function get_value(stdClass $attribute, array $bag, stdClass $idp): ?string {
        $values = $bag[$attribute->idpattr] ?? [];
        $values = array_values(array_filter(array_map('trim', $values), fn($v) => $v !== ''));
        if (!$values) {
            return null;
        }
        $value = $values[0];

        if ($attribute->userfield === 'username') {
            $value = \core_text::strtolower($idp->usernameprefix . $value);
        } else if ($attribute->userfield === 'email') {
            $value = \core_text::strtolower($value);
        }
        return $value;
    }

    /**
     * Values for all mapped fields, keyed by user field name.
     *
     * @param stdClass $idp
     * @param array $bag
     * @param int $minsync only mappings with at least this sync level
     * @return array
     */
    public static function get_values(stdClass $idp, array $bag, int $minsync): array {
        $values = [];
        foreach (self::get_for_idp($idp->id) as $attribute) {
            if ($attribute->sync < $minsync) {
                continue;
            }
            $value = self::get_value($attribute, $bag, $idp);
            if ($value !== null) {
                $values[$attribute->userfield] = $value;
            }
        }
        return $values;
    }

    /**
     * Copy attribute values into an existing user.
     *
     * Username is never changed, it would break the account the user already knows.
     * Only values that really differ are written.
     *
     * @param stdClass $idp
     * @param stdClass $user existing user record
     * @param array $bag attribute bag
     * @param int $minsync sync level to apply, SYNC_ONLOGIN during login
     * @return bool true when something was updated
     */
    public static function sync_user(stdClass $idp, stdClass $user, array $bag, int $minsync): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $values = self::get_values($idp, $bag, $minsync);
        unset($values['username']);

        $update = new stdClass();
        $custom = new stdClass();
        $changed = false;

        foreach ($values as $field => $value) {
            if (userfield::is_profile_field($field)) {
                $shortname = userfield::get_profile_shortname($field);
                $current = profile_user_record($user->id, false);
                if (!property_exists($current, $shortname) || (string)$current->$shortname === (string)$value) {
                    continue;
                }
                $custom->{'profile_field_' . $shortname} = $value;
                $changed = true;
                continue;
            }
            if (!property_exists($user, $field) || (string)$user->$field === (string)$value) {
                continue;
            }
            if ($field === 'email' && (!validate_email($value) || email_is_not_allowed($value))) {
                continue;
            }
            $update->$field = $value;
            $user->$field = $value;
            $changed = true;
        }

        if (!$changed) {
            return false;
        }

        if ((array)$update) {
            $update->id = $user->id;
            $update->timemodified = time();
            $DB->update_record('user', $update);
        }
        if ((array)$custom) {
            $custom->id = $user->id;
            profile_save_data($custom);
        }
        \core\event\user_updated::create_from_userid($user->id)->trigger();

        return true;
    }

    /**
     * Does any IDP synchronise attributes at every login?
     *
     * @return bool
     */
    public static function is_synchronised_on_login(): bool {
        global $DB;
        return $DB->record_exists('auth_musaml_attribute', ['sync' => self::SYNC_ONLOGIN]);
    }

    /**
     * Mappings used for automatic user matching.
     *
     * @param int $idpid
     * @return stdClass[]
     */
    public static function get_usermapping_for_idp(int $idpid): array {
        global $DB;
        return $DB->get_records('auth_musaml_attribute', ['idpid' => $idpid, 'usermapping' => 1], 'id ASC');
    }

    /**
     * All attribute mappings of one IDP.
     *
     * @param int $idpid
     * @return stdClass[]
     */
    public static function get_for_idp(int $idpid): array {
        global $DB;
        return $DB->get_records('auth_musaml_attribute', ['idpid' => $idpid], 'userfield ASC, id ASC');
    }

    /**
     * Delete all attribute mappings of one IDP.
     *
     * @param int $idpid
     */
    public static function delete_for_idp(int $idpid): void {
        global $DB;
        $DB->delete_records('auth_musaml_attribute', ['idpid' => $idpid]);
    }
}
