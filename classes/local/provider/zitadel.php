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

/**
 * Zitadel identity provider.
 *
 * Zitadel advertises its attribute names in metadata and sends the user id
 * both as persistent NameID and as the UserID attribute.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class zitadel extends base {
    #[\Override]
    public static function detect(array $metadata): bool {
        $entityid = $metadata['entityid'] ?? '';
        if (preg_match('#/saml/v2/metadata$#', $entityid)) {
            return true;
        }
        $attributes = $metadata['attributes'] ?? [];
        return in_array('UserID', $attributes, true) && in_array('SurName', $attributes, true);
    }

    #[\Override]
    public static function supports_slo(): bool {
        // Zitadel answers logout requests with RequestDenied and prints the raw XML
        // response instead of sending the user back, so the plugin logs out locally only.
        return false;
    }

    #[\Override]
    public static function get_form_defaults(array $metadata): array {
        $defaults = parent::get_form_defaults($metadata);
        $defaults['name'] = 'Zitadel';
        $defaults['mapattr'] = 'UserID';
        $defaults['attributes'] = [
            ['UserName', 'username', attribute::SYNC_ONCREATE, 1],
            ['Email', 'email', attribute::SYNC_ONLOGIN, 0],
            ['FirstName', 'firstname', attribute::SYNC_ONLOGIN, 0],
            ['SurName', 'lastname', attribute::SYNC_ONLOGIN, 0],
        ];
        return $defaults;
    }
}
