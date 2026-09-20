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

namespace auth_musaml\external\form_autocomplete;

use auth_musaml\local\idp;
use core\context;
use core_external\external_function_parameters;
use core_external\external_value;
use tool_mulib\local\sql;

/**
 * Candidates for a new user mapping of one identity provider.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_mapping_userid extends \tool_mulib\external\form_autocomplete\user {
    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function is_required(): bool {
        return true;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'idpid' => new external_value(PARAM_INT, 'Identity provider id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Find users that may be mapped to the identity provider.
     *
     * @param string $query
     * @param int $idpid
     * @return array
     */
    public static function execute(string $query, int $idpid): array {
        global $DB, $CFG;

        [
            'query' => $query,
            'idpid' => $idpid,
        ] = self::validate_parameters(self::execute_parameters(), ['query' => $query, 'idpid' => $idpid]);

        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('auth/musaml:managemappings', $context);

        $idp = idp::fetch($idpid);
        if (!$idp) {
            throw new \core\exception\invalid_parameter_exception('Invalid identity provider');
        }

        $sql = (new sql(
            "SELECT usr.*
               FROM {user} usr
              WHERE usr.deleted = 0
                    AND NOT EXISTS (
                        SELECT 1 FROM {auth_musaml_user} m WHERE m.userid = usr.id
                    )
                    /* search */ /* tenant */
            /* orderby */"
        ))
            ->replace_comment('search', self::get_user_search_query($query, 'usr', $context)->wrap('AND ', ''))
            ->replace_comment('tenant', self::get_tenant_related_users_where('usr.id', $context)->wrap('AND ', ''))
            ->replace_comment('orderby', self::get_user_search_orderby($query, 'usr', $context)->wrap('ORDER BY ', ''));

        $users = $DB->get_records_sql($sql->sql, $sql->params, 0, $CFG->maxusersperpage + 1);

        return self::prepare_result($users, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, context $context): ?string {
        global $DB;

        if (!$value) {
            return get_string('required');
        }
        $user = $DB->get_record('user', ['id' => $value, 'deleted' => 0]);
        if (!$user) {
            return get_string('error');
        }
        if (\auth_musaml\local\mapping::fetch_by_userid($user->id)) {
            return get_string('error_usermapped', 'auth_musaml');
        }
        return self::validate_tenant_relation($user, $context);
    }
}
