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

use auth_musaml\local\mapping;

/**
 * Delete IDP confirmation form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $idp = $this->_customdata['idp'];

        $count = mapping::count_for_idp($idp->id);
        $info = '<div class="alert alert-danger">' . get_string('idp_delete_info', 'auth_musaml', $count) . '</div>';
        $mform->addElement('html', $info);
        $mform->addElement('static', 'staticname', get_string('idp_name', 'auth_musaml'), format_string($idp->name));
        $mform->addElement('static', 'staticentityid', get_string('idp_entityid', 'auth_musaml'), s($idp->entityid));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $idp->id);

        $this->add_action_buttons(true, get_string('idp_delete', 'auth_musaml'));
    }
}
