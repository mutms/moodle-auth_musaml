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
 * Google Workspace.
 *
 * Google sends the primary email address as NameID and offers no single logout,
 * so logging out of this site does not end the Google session.
 *
 * NOTE: defaults are based on the Google Workspace documentation, they still need to
 * be verified against a real domain.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class google extends base {
    #[\Override]
    public static function detect(array $metadata): bool {
        $entityid = $metadata['entityid'] ?? '';
        if (preg_match('#^https://accounts\.google\.com/o/saml2#i', $entityid)) {
            return true;
        }
        return (bool)preg_match('#accounts\.google\.com/o/saml2#i', $metadata['ssourl'] ?? '');
    }

    #[\Override]
    public static function supports_slo(): bool {
        // Google Workspace publishes no single logout service for SAML applications.
        return false;
    }

    #[\Override]
    public static function get_form_defaults(array $metadata): array {
        $defaults = parent::get_form_defaults($metadata);
        $defaults['name'] = 'Google Workspace';
        $defaults['mapattr'] = self::NAMEID_ATTRIBUTE;
        $defaults['attributes'] = [
            // Google has no username, the NameID holds the email address and serves as one.
            [self::NAMEID_ATTRIBUTE, 'username', attribute::SYNC_ONCREATE, 1],
            ['email', 'email', attribute::SYNC_ONLOGIN, 0],
            ['first_name', 'firstname', attribute::SYNC_ONLOGIN, 0],
            ['last_name', 'lastname', attribute::SYNC_ONLOGIN, 0],
        ];
        return $defaults;
    }

    #[\Override]
    public static function adjust_settings(array $settings, stdClass $idp): array {
        // Google issues the email address as NameID and rejects other formats.
        $settings['sp']['NameIDFormat'] = \OneLogin\Saml2\Constants::NAMEID_EMAIL_ADDRESS;
        return $settings;
    }
}
