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

namespace auth_musaml\muform\userpicker;

use auth_musaml\local\idp;
use auth_musaml\local\mapping;
use tool_mulib\local\sql;

/**
 * Candidates for a new user mapping of one identity provider.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_mapping_userid extends \tool_mulib\muform\userpicker\base {
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
    protected function get_context(): \core\context {
        return \core\context\system::instance();
    }

    #[\Override]
    protected function get_candidates_sql(): ?sql {
        global $CFG, $DB;

        // A user may have only one mapping, mapped users are not offered at all.
        $where = "NOT EXISTS (SELECT 1 FROM {auth_musaml_user} m WHERE m.userid = u.id)";
        $params = [];
        $admins = array_filter(array_map('intval', explode(',', $CFG->siteadmins)));
        if ($admins && !is_siteadmin()) {
            [$notadmin, $params] = $DB->get_in_or_equal($admins, SQL_PARAMS_NAMED, 'musamladmin', false);
            $where .= " AND u.id $notadmin";
        }
        return new sql($where, $params);
    }

    /**
     * Any user may be submitted, mapped users are refused with a reason in validation.
     *
     * @return sql|null
     */
    #[\Override]
    protected function get_selected_sql(): ?sql {
        return null;
    }

    #[\Override]
    public function validate(array $values): array {
        $errors = parent::validate($values);
        foreach ($values as $value) {
            if (!mapping::can_manage_user((int)$value)) {
                $errors[(string)$value] = get_string('error_siteadminmapping', 'auth_musaml');
            } else if (mapping::fetch_by_userid((int)$value)) {
                $errors[(string)$value] = get_string('error_usermapped', 'auth_musaml');
            }
        }
        return $errors;
    }
}
