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
use core_external\external_value;

/**
 * Remove the mapping of a user to an identity provider account.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class delete_user_mapping extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entityid' => new external_value(PARAM_RAW_TRIMMED, 'Identity provider entity ID'),
            'guid' => new external_value(PARAM_RAW_TRIMMED, 'User id in the identity provider'),
        ]);
    }

    /**
     * Delete the mapping, returns false when there was nothing to delete.
     *
     * @param string $entityid
     * @param string $guid
     * @return bool
     */
    public static function execute(string $entityid, string $guid): bool {
        ['entityid' => $entityid, 'guid' => $guid] = self::validate_parameters(
            self::execute_parameters(),
            ['entityid' => $entityid, 'guid' => $guid]
        );

        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('auth/musaml:managemappings', $context);

        $idp = idp::fetch_by_entityid($entityid);
        if (!$idp) {
            throw new invalid_parameter_exception('Unknown identity provider: ' . $entityid);
        }

        $mapping = mapping::fetch_by_guid($idp->id, $guid);
        if (!$mapping) {
            return false;
        }
        if (is_siteadmin($mapping->userid) && !is_siteadmin()) {
            throw new invalid_parameter_exception('Site administrators cannot be managed through web services');
        }

        mapping::delete($mapping->id);

        return true;
    }

    /**
     * Describes the external function result value.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'True when a mapping was deleted');
    }
}
