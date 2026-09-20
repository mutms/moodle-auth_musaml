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

/**
 * Behat data generator for SAML auth.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class behat_auth_musaml_generator extends behat_generator_base {
    #[\Override]
    protected function get_creatable_entities(): array {
        return [
            'idps' => [
                'singular' => 'idp',
                'datagenerator' => 'idp',
                'required' => ['name'],
            ],
            'attributes' => [
                'singular' => 'attribute',
                'datagenerator' => 'attribute',
                'required' => ['idp', 'idpattr', 'userfield'],
            ],
            'user mappings' => [
                'singular' => 'user mapping',
                'datagenerator' => 'user_mapping',
                'required' => ['idp', 'user'],
            ],
        ];
    }
}
