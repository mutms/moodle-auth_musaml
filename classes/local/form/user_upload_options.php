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

use auth_musaml\local\mapping_import;

/**
 * Mapping import, stage three: options and the dry run result.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_upload_options extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $idp = $this->_customdata['idp'];
        $draftid = $this->_customdata['draftid'];
        $columns = $this->_customdata['columns'];

        $mform->addElement('hidden', 'idpid');
        $mform->setType('idpid', PARAM_INT);
        $mform->setConstant('idpid', $idp->id);

        $mform->addElement('hidden', 'draftid');
        $mform->setType('draftid', PARAM_INT);
        $mform->setConstant('draftid', $draftid);

        foreach ($columns as $index => $value) {
            $mform->addElement('hidden', 'column_' . $index);
            $mform->setType('column_' . $index, PARAM_ALPHANUMEXT);
            $mform->setConstant('column_' . $index, $value);
        }

        // What happens to the accounts comes first, the skip list below depends on it.
        $mform->addElement('advcheckbox', 'allowotherauth', get_string('user_mapping_allowotherauth', 'auth_musaml'));
        $mform->addHelpButton('allowotherauth', 'user_mapping_allowotherauth', 'auth_musaml');

        $mform->addElement('advcheckbox', 'setauth', get_string('user_mapping_setauth', 'auth_musaml'));
        $mform->setDefault('setauth', 1);
        $mform->addHelpButton('setauth', 'user_mapping_setauth', 'auth_musaml');

        $mform->addElement('static', 'skipinfo', '', get_string('import_skip_info', 'auth_musaml'));
        foreach (mapping_import::get_skip_menu() as $name => $label) {
            $mform->addElement('advcheckbox', $name, $label);
            $mform->setDefault($name, 1);
        }

        $this->add_action_buttons(true, get_string('import_preview', 'auth_musaml'));
    }
}
