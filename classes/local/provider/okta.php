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
 * Okta.
 *
 * Okta applications define their own attribute statements, the defaults here match
 * the attribute names the Okta setup guides use.
 *
 * NOTE: defaults are based on the Okta documentation, they still need to be verified
 * against a real organisation.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class okta extends base {
    #[\Override]
    public static function detect(array $metadata): bool {
        $entityid = $metadata['entityid'] ?? '';
        if (preg_match('#^http://www\.okta\.com/#i', $entityid)) {
            return true;
        }
        return (bool)preg_match('#\.okta(preview)?\.com/#i', $metadata['ssourl'] ?? '');
    }

    #[\Override]
    public static function get_form_defaults(array $metadata): array {
        $defaults = parent::get_form_defaults($metadata);
        $defaults['name'] = 'Okta';
        // The NameID is the login name, which people change. The internal record id of
        // Okta never changes, so the app must send it as an "id" attribute statement.
        $defaults['mapattr'] = 'id';
        $defaults['attributes'] = [
            ['login', 'username', attribute::SYNC_ONCREATE, 1],
            ['email', 'email', attribute::SYNC_ONLOGIN, 0],
            ['firstName', 'firstname', attribute::SYNC_ONLOGIN, 0],
            ['lastName', 'lastname', attribute::SYNC_ONLOGIN, 0],
        ];
        return $defaults;
    }
}
