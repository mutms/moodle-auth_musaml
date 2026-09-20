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

namespace auth_musaml\local\form;

/**
 * Delete user mapping confirmation form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $mapping = $this->_customdata['mapping'];
        $user = $this->_customdata['user'];

        $info = '<div class="alert alert-warning">' . get_string('user_mapping_delete_info', 'auth_musaml') . '</div>';
        $mform->addElement('html', $info);
        $userlabel = fullname($user) . ' (' . s($user->username) . ')';
        $mform->addElement('static', 'staticuser', get_string('user_mapping_user', 'auth_musaml'), $userlabel);
        $mform->addElement('static', 'staticguid', get_string('user_mapping_guid', 'auth_musaml'), s($mapping->guid));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $mapping->id);

        $this->add_action_buttons(true, get_string('user_mapping_delete', 'auth_musaml'));
    }
}
