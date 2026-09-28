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
 * SAML authentication web services.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'auth_musaml_create_user_mapping' => [
        'classname' => auth_musaml\external\create_user_mapping::class,
        'description' => 'Maps a user to an identity provider account.',
        'type' => 'write',
        'capabilities' => 'auth/musaml:managemappings',
        'ajax' => false,
        'loginrequired' => true,
    ],
    'auth_musaml_delete_user_mapping' => [
        'classname' => auth_musaml\external\delete_user_mapping::class,
        'description' => 'Removes the mapping of a user to an identity provider account.',
        'type' => 'write',
        'capabilities' => 'auth/musaml:managemappings',
        'ajax' => false,
        'loginrequired' => true,
    ],
];
