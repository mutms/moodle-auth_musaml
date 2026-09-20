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

use auth_musaml\local\attribute;
use auth_musaml\local\idp;
use auth_musaml\local\userfield;

/**
 * Create and update form for attribute mappings.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attribute_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $idp = $this->_customdata['idp'];
        $attribute = $this->_customdata['attribute'] ?? null;

        $mform->addElement('hidden', 'idpid');
        $mform->setType('idpid', PARAM_INT);
        $mform->setConstant('idpid', $idp->id);

        if ($attribute) {
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
            $mform->setConstant('id', $attribute->id);
        }

        // Without a placeholder the first field looks preselected and everything becomes a username.
        $menu = ['' => get_string('choosedots')] + userfield::get_menu();
        $mform->addElement('select', 'userfield', get_string('attribute_userfield', 'auth_musaml'), $menu);
        $mform->addRule('userfield', null, 'required', null, 'client');
        $mform->addHelpButton('userfield', 'attribute_userfield', 'auth_musaml');

        $mform->addElement('text', 'idpattr', get_string('attribute_idpattr', 'auth_musaml'), 'size="50"');
        $mform->setType('idpattr', PARAM_RAW_TRIMMED);
        if (!$attribute && !empty($this->_customdata['idpattr'])) {
            $mform->setDefault('idpattr', $this->_customdata['idpattr']);
        }
        $mform->addRule('idpattr', null, 'required', null, 'client');
        $mform->addHelpButton('idpattr', 'attribute_idpattr', 'auth_musaml');

        $advertised = idp::get_certinfo($idp)['attributes'];
        if ($advertised) {
            $hint = get_string('attribute_advertised', 'auth_musaml', s(implode(', ', $advertised)));
            $mform->addElement('static', 'advertised', '', $hint);
        }

        $mform->addElement('select', 'sync', get_string('attribute_sync', 'auth_musaml'), attribute::get_sync_menu());
        $mform->setDefault('sync', attribute::SYNC_ONLOGIN);
        $mform->addHelpButton('sync', 'attribute_sync', 'auth_musaml');

        $mform->addElement('advcheckbox', 'usermapping', get_string('attribute_usermapping', 'auth_musaml'));
        $mform->addHelpButton('usermapping', 'attribute_usermapping', 'auth_musaml');

        if ($attribute) {
            $this->set_data($attribute);
        }
        $label = $attribute ? 'attribute_update' : 'attribute_create';
        $this->add_action_buttons(true, get_string($label, 'auth_musaml'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $idp = $this->_customdata['idp'];
        $attribute = $this->_customdata['attribute'] ?? null;

        if (trim($data['idpattr']) === '') {
            $errors['idpattr'] = get_string('required');
        }
        if (!array_key_exists($data['userfield'], userfield::get_menu())) {
            $errors['userfield'] = get_string('required');
        } else if (attribute::userfield_exists($idp->id, $data['userfield'], $attribute->id ?? 0)) {
            $errors['userfield'] = get_string('error_userfieldmapped', 'auth_musaml');
        }
        $syncsonlogin = (int)$data['sync'] === attribute::SYNC_ONLOGIN;
        if ($data['userfield'] === 'username' && $syncsonlogin) {
            $errors['sync'] = get_string('error_usernamesync', 'auth_musaml');
        }
        return $errors;
    }
}
