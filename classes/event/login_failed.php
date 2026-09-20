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
 * Somebody could not log in through an identity provider.
 *
 * The reason is the part administrators need, the core failed login event does not
 * carry it.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 * @var string $reason error string identifier
 * @var string $detail failing value, for example the identity provider account id
 * @var int $idpid identity provider id
 * }
 */
final class login_failed extends base {
    /**
     * Create the event from the identity provider and the reason the login failed.
     *
     * @param stdClass $idp
     * @param string $reason error string identifier
     * @param string|null $detail failing value, for example the identity provider account id
     * @return self
     */
    public static function create_from_idp(stdClass $idp, string $reason, ?string $detail = null): self {
        return self::create([
            'context' => \core\context\system::instance(),
            'other' => [
                'reason' => $reason,
                'detail' => (string)$detail,
                'idpid' => (int)$idp->id,
            ],
        ]);
    }

    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('event_login_failed', 'auth_musaml');
    }

    #[\Override]
    public function get_description(): string {
        $reason = get_string($this->other['reason'], 'auth_musaml', s($this->other['detail']));
        return "Login through identity provider {$this->other['idpid']} failed: " . $reason;
    }

    #[\Override]
    protected function validate_data(): void {
        parent::validate_data();
        foreach (['reason', 'detail', 'idpid'] as $key) {
            if (!isset($this->other[$key])) {
                throw new \coding_exception('The ' . $key . ' value must be set in other.');
            }
        }
    }
}
