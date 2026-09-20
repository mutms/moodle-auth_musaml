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

use auth_musaml\local\openssl;
use auth_musaml\local\saml;

/**
 * Regenerate SP certificate form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sp_cert_regen extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $info = $this->_customdata['info'];

        if ($info) {
            $warning = '<div class="alert alert-warning">' . get_string('sp_cert_regen_warning', 'auth_musaml') . '</div>';
            $mform->addElement('html', $warning);
        }

        $mform->addElement('text', 'entityid', get_string('sp_entityid', 'auth_musaml'), 'size="50"');
        $mform->setType('entityid', PARAM_URL);
        $mform->setDefault('entityid', saml::get_sp_entityid());
        $mform->addRule('entityid', null, 'required', null, 'client');
        $mform->addHelpButton('entityid', 'sp_entityid', 'auth_musaml');

        $dn = saml::get_default_dn();

        $mform->addElement('text', 'commonname', get_string('sp_cert_commonname', 'auth_musaml'), 'size="50"');
        $mform->setType('commonname', PARAM_TEXT);
        $mform->setDefault('commonname', $dn['commonName']);
        $mform->addRule('commonname', null, 'required', null, 'client');

        $mform->addElement('text', 'organizationname', get_string('sp_cert_organizationname', 'auth_musaml'), 'size="50"');
        $mform->setType('organizationname', PARAM_TEXT);
        $mform->setDefault('organizationname', $dn['organizationName']);
        $mform->addRule('organizationname', null, 'required', null, 'client');

        $mform->addElement('text', 'days', get_string('sp_cert_days', 'auth_musaml'), 'size="6"');
        $mform->setType('days', PARAM_INT);
        $mform->setDefault('days', openssl::CERT_DAYS);
        $mform->addRule('days', null, 'required', null, 'client');

        $label = $info ? 'sp_cert_regen' : 'sp_cert_create';
        $this->add_action_buttons(true, get_string($label, 'auth_musaml'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if ($data['days'] < 1 || $data['days'] > 36500) {
            $errors['days'] = get_string('error');
        }
        return $errors;
    }
}
