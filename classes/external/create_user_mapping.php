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

namespace auth_musaml\external;

use auth_musaml\local\idp;
use auth_musaml\local\mapping;
use core\exception\invalid_parameter_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Map a user to an identity provider account.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class create_user_mapping extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entityid' => new external_value(PARAM_RAW_TRIMMED, 'Identity provider entity ID'),
            'guid' => new external_value(PARAM_RAW_TRIMMED, 'User id in the identity provider'),
            'userid' => new external_value(PARAM_INT, 'Moodle user id'),
            'allowotherauth' => new external_value(
                PARAM_BOOL,
                'Keep the current authentication method usable',
                VALUE_DEFAULT,
                false
            ),
            'setauth' => new external_value(
                PARAM_BOOL,
                'Switch the user account to SAML authentication',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    /**
     * Create the mapping.
     *
     * @param string $entityid
     * @param string $guid
     * @param int $userid
     * @param bool $allowotherauth
     * @param bool $setauth
     * @return array
     */
    public static function execute(
        string $entityid,
        string $guid,
        int $userid,
        bool $allowotherauth = false,
        bool $setauth = false
    ): array {
        global $DB;

        [
            'entityid' => $entityid,
            'guid' => $guid,
            'userid' => $userid,
            'allowotherauth' => $allowotherauth,
            'setauth' => $setauth,
        ] = self::validate_parameters(self::execute_parameters(), [
            'entityid' => $entityid,
            'guid' => $guid,
            'userid' => $userid,
            'allowotherauth' => $allowotherauth,
            'setauth' => $setauth,
        ]);

        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('auth/musaml:managemappings', $context);

        $idp = idp::fetch_by_entityid($entityid);
        if (!$idp) {
            throw new invalid_parameter_exception('Unknown identity provider: ' . $entityid);
        }
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if (!$user) {
            throw new invalid_parameter_exception('Unknown user: ' . $userid);
        }
        // Mapping a site administrator would hand control of that account to the identity provider.
        if (is_siteadmin($user->id) && !is_siteadmin()) {
            throw new invalid_parameter_exception('Site administrators cannot be mapped through web services');
        }

        $mapping = mapping::create((object)[
            'idpid' => $idp->id,
            'userid' => $user->id,
            'guid' => $guid,
            'allowotherauth' => $allowotherauth,
        ]);
        if ($setauth) {
            mapping::set_user_auth($user->id);
        }

        return [
            'id' => (int)$mapping->id,
            'idpid' => (int)$mapping->idpid,
            'userid' => (int)$mapping->userid,
            'guid' => $mapping->guid,
            'allowotherauth' => (bool)$mapping->allowotherauth,
            'timecreated' => (int)$mapping->timecreated,
        ];
    }

    /**
     * Describes the external function result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Mapping id'),
            'idpid' => new external_value(PARAM_INT, 'Identity provider id'),
            'userid' => new external_value(PARAM_INT, 'Moodle user id'),
            'guid' => new external_value(PARAM_RAW, 'User id in the identity provider'),
            'allowotherauth' => new external_value(PARAM_BOOL, 'Other authentication methods allowed'),
            'timecreated' => new external_value(PARAM_INT, 'Time the mapping was created'),
        ]);
    }
}
