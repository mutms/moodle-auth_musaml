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
 * Delete attribute mapping confirmation form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attribute_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $attribute = $this->_customdata['attribute'];

        $info = '<div class="alert alert-warning">' . get_string('attribute_delete_info', 'auth_musaml') . '</div>';
        $mform->addElement('html', $info);
        $userfieldlabel = get_string('attribute_userfield', 'auth_musaml');
        $mform->addElement('static', 'staticuserfield', $userfieldlabel, s($attribute->userfield));
        $idpattrlabel = get_string('attribute_idpattr', 'auth_musaml');
        $mform->addElement('static', 'staticidpattr', $idpattrlabel, s($attribute->idpattr));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $attribute->id);

        $this->add_action_buttons(true, get_string('attribute_delete', 'auth_musaml'));
    }
}
