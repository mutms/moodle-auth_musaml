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
 * An identity provider was deleted.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 * @var string $entityid entity ID of the identity provider
 * @var string $name name shown on the login page
 * }
 */
final class idp_deleted extends base {
    /**
     * Create the event from an identity provider record.
     *
     * @param stdClass $idp
     * @return self
     */
    public static function create_from_idp(stdClass $idp): self {
        /** @var self $event */
        $event = self::create([
            'objectid' => $idp->id,
            'context' => \core\context\system::instance(),
            'other' => [
                'entityid' => $idp->entityid,
                'name' => $idp->name,
            ],
        ]);
        $event->add_record_snapshot('auth_musaml_idp', $idp);

        return $event;
    }

    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'auth_musaml_idp';
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('event_idp_deleted', 'auth_musaml');
    }

    #[\Override]
    public function get_description(): string {
        return "The identity provider '{$this->other['name']}' with entity ID " .
            "'{$this->other['entityid']}' was deleted.";
    }
}
