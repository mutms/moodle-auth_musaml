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

namespace auth_musaml\muform\autocomplete;

use auth_musaml\local\idp;
use auth_musaml\local\mapping;
use core\context;
use tool_mulib\local\sql;
use tool_mulib\muform\util\autocomplete\user_trait;

/**
 * Candidates for a new user mapping of one identity provider.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_mapping_userid extends \tool_mulib\muform\autocomplete\base {
    use user_trait;

    /** @var int identity provider id */
    private int $idpid;

    /**
     * Constructor.
     *
     * @param int $idpid identity provider the new mapping belongs to
     */
    public function __construct(int $idpid) {
        require_capability('auth/musaml:managemappings', \core\context\system::instance());
        if (!idp::fetch($idpid)) {
            throw new \core\exception\invalid_parameter_exception('Invalid identity provider');
        }
        $this->idpid = $idpid;
    }

    #[\Override]
    public function get_args(): array {
        return [$this->idpid];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        // A user may have only one mapping, mapped users are not offered at all.
        $where = new sql("NOT EXISTS (SELECT 1 FROM {auth_musaml_user} m WHERE m.userid = u.id)");
        return $this->search_users(context\system::instance(), $query, $maxitems, [], $where);
    }

    #[\Override]
    public function label(string $value): ?string {
        return $this->user_labels(context\system::instance(), [$value])[$value] ?? null;
    }

    #[\Override]
    public function validate(string $value): ?string {
        if (mapping::fetch_by_userid((int)$value)) {
            return get_string('error_usermapped', 'auth_musaml');
        }
        return null;
    }
}
