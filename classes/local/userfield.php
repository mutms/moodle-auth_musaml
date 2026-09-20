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
 * Moodle user fields that attribute mappings can fill.
 *
 * Field locking is not handled here, it uses the standard authentication plugin
 * settings that core builds with display_auth_lock_options().
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class userfield {
    /** @var string prefix of custom profile fields in plugin data */
    public const PROFILE_PREFIX = 'profile_';

    /**
     * User table fields that may be filled from IDP attributes.
     *
     * @return string[]
     */
    public static function get_core_fields(): array {
        return [
            'username', 'email', 'firstname', 'lastname', 'idnumber', 'phone1', 'phone2',
            'institution', 'department', 'address', 'city', 'country', 'lang', 'timezone',
            'description', 'firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename',
        ];
    }

    /**
     * Fields that must be present before a user account can be created.
     *
     * @return string[]
     */
    public static function get_required_fields(): array {
        return ['username', 'email', 'firstname', 'lastname'];
    }

    /**
     * All usable fields for selects.
     *
     * @return array field name => label, custom profile fields use the profile_ prefix
     */
    public static function get_menu(): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        // Core has no string named after every user table column.
        $labels = ['lang' => 'language', 'description' => 'userdescription'];

        $menu = [];
        foreach (self::get_core_fields() as $field) {
            $menu[$field] = get_string($labels[$field] ?? $field, 'core');
        }
        foreach (profile_get_custom_fields() as $field) {
            $menu[self::PROFILE_PREFIX . $field->shortname] = format_string($field->name);
        }
        return $menu;
    }

    /**
     * Fields locked on a new site, identity providers own their values.
     *
     * @return string[]
     */
    public static function get_default_locked(): array {
        return ['firstname', 'lastname', 'email'];
    }

    /**
     * Is this a custom profile field?
     *
     * @param string $field
     * @return bool
     */
    public static function is_profile_field(string $field): bool {
        return strpos($field, self::PROFILE_PREFIX) === 0;
    }

    /**
     * Short name of a custom profile field.
     *
     * @param string $field
     * @return string
     */
    public static function get_profile_shortname(string $field): string {
        return substr($field, strlen(self::PROFILE_PREFIX));
    }
}
