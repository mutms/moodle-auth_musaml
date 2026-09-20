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

use auth_musaml\external\form_autocomplete\user_mapping_userid;
use auth_musaml\local\mapping;

/**
 * Create user mapping form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $idp = $this->_customdata['idp'];
        $context = \core\context\system::instance();

        $mform->addElement('hidden', 'idpid');
        $mform->setType('idpid', PARAM_INT);
        $mform->setConstant('idpid', $idp->id);

        $userlabel = get_string('user_mapping_user', 'auth_musaml');
        user_mapping_userid::add_element($mform, ['idpid' => $idp->id], 'userid', $userlabel, $context);
        $mform->addHelpButton('userid', 'user_mapping_user', 'auth_musaml');

        $mform->addElement('text', 'guid', get_string('user_mapping_guid', 'auth_musaml'), 'size="50"');
        $mform->setType('guid', PARAM_RAW_TRIMMED);
        $mform->addRule('guid', null, 'required', null, 'client');
        $mform->addHelpButton('guid', 'user_mapping_guid', 'auth_musaml');

        $mform->addElement('advcheckbox', 'allowotherauth', get_string('user_mapping_allowotherauth', 'auth_musaml'));
        $mform->addHelpButton('allowotherauth', 'user_mapping_allowotherauth', 'auth_musaml');

        $mform->addElement('advcheckbox', 'setauth', get_string('user_mapping_setauth', 'auth_musaml'));
        $mform->setDefault('setauth', 1);
        $mform->addHelpButton('setauth', 'user_mapping_setauth', 'auth_musaml');

        $this->add_action_buttons(true, get_string('user_mapping_create', 'auth_musaml'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $idp = $this->_customdata['idp'];

        $guid = trim($data['guid']);
        if ($guid === '') {
            $errors['guid'] = get_string('required');
        } else if (\core_text::strlen($guid) > 255) {
            $errors['guid'] = get_string('error');
        } else if (mapping::fetch_by_guid($idp->id, $guid)) {
            $errors['guid'] = get_string('error_guidmapped', 'auth_musaml');
        }
        return $errors;
    }
}
