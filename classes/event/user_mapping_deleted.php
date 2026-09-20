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

namespace auth_musaml\event;

use core\event\base;
use stdClass;

/**
 * A user is no longer mapped to an identity provider account.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 * @var string $guid user id in the identity provider
 * @var int $idpid identity provider id
 * }
 */
final class user_mapping_deleted extends base {
    /**
     * Create the event from a user mapping record.
     *
     * @param stdClass $mapping
     * @return self
     */
    public static function create_from_mapping(stdClass $mapping): self {
        /** @var self $event */
        $event = self::create([
            'objectid' => $mapping->id,
            'relateduserid' => $mapping->userid,
            'context' => \core\context\system::instance(),
            'other' => [
                'guid' => $mapping->guid,
                'idpid' => (int)$mapping->idpid,
            ],
        ]);
        $event->add_record_snapshot('auth_musaml_user', $mapping);

        return $event;
    }

    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'auth_musaml_user';
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('event_user_mapping_deleted', 'auth_musaml');
    }

    #[\Override]
    public function get_description(): string {
        return "The mapping of the user with id '{$this->relateduserid}' to account " .
            "'{$this->other['guid']}' of identity provider {$this->other['idpid']} was removed.";
    }
}
