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
 * Microsoft Active Directory Federation Services.
 *
 * ADFS percent encodes redirect binding parameters in lower case, which breaks
 * signature verification unless the library is told about it.
 *
 * NOTE: defaults are based on the ADFS documentation, they still need to be verified
 * against a real server, in particular which claim a site publishes as the stable id.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class adfs extends base {
    /** @var string prefix of the classic claim names */
    public const CLAIM_PREFIX = 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/';

    /** @var string claim usually configured as the stable id */
    public const CLAIM_PRIMARYSID = 'http://schemas.microsoft.com/ws/2008/06/identity/claims/primarysid';

    #[\Override]
    public static function detect(array $metadata): bool {
        $entityid = $metadata['entityid'] ?? '';
        if (preg_match('#/adfs/services/trust/?$#i', $entityid)) {
            return true;
        }
        return (bool)preg_match('#/adfs/ls/?#i', $metadata['ssourl'] ?? '');
    }

    #[\Override]
    public static function get_form_defaults(array $metadata): array {
        $defaults = parent::get_form_defaults($metadata);
        $defaults['name'] = 'Active Directory Federation Services';
        $defaults['mapattr'] = self::CLAIM_PRIMARYSID;
        $defaults['attrsimple'] = 1;
        $defaults['attributes'] = [
            [self::CLAIM_PREFIX . 'name', 'username', attribute::SYNC_ONCREATE, 1],
            [self::CLAIM_PREFIX . 'emailaddress', 'email', attribute::SYNC_ONLOGIN, 0],
            [self::CLAIM_PREFIX . 'givenname', 'firstname', attribute::SYNC_ONLOGIN, 0],
            [self::CLAIM_PREFIX . 'surname', 'lastname', attribute::SYNC_ONLOGIN, 0],
        ];
        return $defaults;
    }

    #[\Override]
    public static function adjust_settings(array $settings, stdClass $idp): array {
        // ADFS signs redirect binding parameters after encoding them in lower case.
        $settings['security']['lowercaseUrlencoding'] = true;
        return $settings;
    }
}
