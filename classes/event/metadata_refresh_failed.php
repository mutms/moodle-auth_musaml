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
 * Identity provider metadata could not be downloaded or parsed.
 *
 * The stored certificates stay in use, but they will eventually expire, so this is
 * the event that explains logins breaking days later.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 * @var string $error what went wrong
 * @var string $url metadata URL
 * }
 */
final class metadata_refresh_failed extends base {
    /**
     * Create the event from an identity provider and the error it reported.
     *
     * @param stdClass $idp
     * @param string $error
     * @return self
     */
    public static function create_from_idp(stdClass $idp, string $error): self {
        return self::create([
            'objectid' => $idp->id,
            'context' => \core\context\system::instance(),
            'other' => [
                'error' => $error,
                'url' => $idp->metadataurl,
            ],
        ]);
    }

    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'auth_musaml_idp';
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('event_metadata_refresh_failed', 'auth_musaml');
    }

    #[\Override]
    public function get_description(): string {
        return "Metadata of identity provider {$this->objectid} could not be refreshed " .
            "from '{$this->other['url']}': {$this->other['error']}";
    }
}
