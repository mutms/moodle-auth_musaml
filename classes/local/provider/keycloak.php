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

namespace auth_musaml\local\provider;

use auth_musaml\local\attribute;
use stdClass;

/**
 * Keycloak and products built on it, such as Red Hat build of Keycloak.
 *
 * A fresh realm sends no attributes at all, only a persistent NameID, so that is the
 * safe default for the user id. Attribute names depend on the mappers a realm defines.
 *
 * NOTE: defaults are based on the Keycloak documentation, they still need to be
 * verified against a real realm.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class keycloak extends base {
    #[\Override]
    public static function detect(array $metadata): bool {
        $entityid = $metadata['entityid'] ?? '';
        if (preg_match('#/realms/[^/]+/?$#i', $entityid)) {
            return true;
        }
        return (bool)preg_match('#/protocol/saml#i', $metadata['ssourl'] ?? '');
    }

    #[\Override]
    public static function get_form_defaults(array $metadata): array {
        $defaults = parent::get_form_defaults($metadata);
        $defaults['name'] = 'Keycloak';
        $defaults['mapattr'] = self::NAMEID_ATTRIBUTE;
        $defaults['attributes'] = [
            ['username', 'username', attribute::SYNC_ONCREATE, 1],
            ['email', 'email', attribute::SYNC_ONLOGIN, 0],
            ['firstName', 'firstname', attribute::SYNC_ONLOGIN, 0],
            ['lastName', 'lastname', attribute::SYNC_ONLOGIN, 0],
        ];
        return $defaults;
    }

    #[\Override]
    public static function adjust_settings(array $settings, stdClass $idp): array {
        // Keycloak answers with the username as NameID unless a format is requested,
        // and a username is not permanent. Ask for the persistent identifier.
        $settings['sp']['NameIDFormat'] = \OneLogin\Saml2\Constants::NAMEID_PERSISTENT;

        // Every realm ships the role_list scope, which sends one Attribute named
        // Role per role. Rejecting repeated names would break every such realm.
        $settings['security']['allowRepeatAttributeName'] = true;

        return $settings;
    }
}
