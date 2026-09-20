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

use stdClass;

/**
 * Identity provider product knowledge.
 *
 * Providers detect themselves from metadata, prefill the IDP form and adjust
 * php-saml settings for product quirks. The admin JSON override is applied after
 * the provider, so admins always have the last word.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var string synthetic attribute name carrying the NameID value */
    public const NAMEID_ATTRIBUTE = 'nameid';

    /**
     * Provider identifier, equals the class short name.
     *
     * @return string
     */
    final public static function get_type(): string {
        $parts = explode('\\', static::class);
        return end($parts);
    }

    /**
     * Human readable provider name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('provider_' . static::get_type(), 'auth_musaml');
    }

    /**
     * Does the parsed metadata look like this provider?
     *
     * @param array $metadata result of saml::parse_idp_metadata()
     * @return bool
     */
    abstract public static function detect(array $metadata): bool;

    /**
     * Defaults for the IDP create form.
     *
     * Keys: name, mapattr, attrsimple; and attributes as a list of
     * [idpattr, userfield, sync, usermapping] arrays.
     *
     * @param array $metadata result of saml::parse_idp_metadata()
     * @return array
     */
    public static function get_form_defaults(array $metadata): array {
        $host = parse_url($metadata['entityid'] ?? '', PHP_URL_HOST);
        return [
            'name' => $host ?: ($metadata['entityid'] ?? ''),
            'mapattr' => self::NAMEID_ATTRIBUTE,
            'attrsimple' => 0,
            'attributes' => [],
        ];
    }

    /**
     * Icon shown on the login page button.
     *
     * @return \core\output\pix_icon
     */
    public static function get_icon(): \core\output\pix_icon {
        return new \core\output\pix_icon('i/user', '');
    }

    /**
     * Can the plugin ask this product to end the session at logout?
     *
     * Products that refuse logout requests or answer them without sending the user
     * back must say so here, otherwise people end up stranded on the provider.
     *
     * @return bool
     */
    public static function supports_slo(): bool {
        return true;
    }

    /**
     * Adjust php-saml settings for product quirks.
     *
     * @param array $settings merged plugin defaults and IDP data
     * @param stdClass $idp
     * @return array
     */
    public static function adjust_settings(array $settings, stdClass $idp): array {
        return $settings;
    }

    /**
     * Normalise attributes received from the IDP before mapping.
     *
     * The bag contains all assertion attributes plus the NameID under 'nameid',
     * every value is a list of strings.
     *
     * @param array $attributes
     * @param stdClass $idp
     * @return array
     */
    public static function filter_attributes(array $attributes, stdClass $idp): array {
        return $attributes;
    }

    /**
     * All available provider types.
     *
     * @return array<string, class-string<base>> type => class name, generic first
     */
    public static function get_all(): array {
        $result = ['generic' => generic::class];
        foreach (\core_component::get_component_classes_in_namespace('auth_musaml', 'local\\provider') as $class => $unused) {
            $rc = new \ReflectionClass($class);
            if ($rc->isAbstract() || !$rc->isSubclassOf(self::class)) {
                continue;
            }
            $result[$class::get_type()] = $class;
        }
        return $result;
    }

    /**
     * Provider menu for forms.
     *
     * @return array type => name
     */
    public static function get_menu(): array {
        $menu = [];
        foreach (self::get_all() as $type => $class) {
            $menu[$type] = $class::get_name();
        }
        return $menu;
    }

    /**
     * Provider class for the type, generic if unknown.
     *
     * @param string|null $type
     * @return class-string<base>
     */
    public static function get_class(?string $type): string {
        $all = self::get_all();
        return $all[$type ?? ''] ?? generic::class;
    }

    /**
     * Provider class detected from metadata, generic if nothing matches.
     *
     * @param array $metadata
     * @return class-string<base>
     */
    public static function detect_class(array $metadata): string {
        foreach (self::get_all() as $type => $class) {
            if ($type === 'generic') {
                continue;
            }
            if ($class::detect($metadata)) {
                return $class;
            }
        }
        return generic::class;
    }
}
