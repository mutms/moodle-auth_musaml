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

use auth_musaml\local\idp;

/**
 * Identity provider certificate handling form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certificate_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $idp = $this->_customdata['idp'];
        $info = idp::get_certinfo($idp);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $idp->id);

        $mform->addElement('advcheckbox', 'autorefresh', get_string('idp_autorefresh', 'auth_musaml'));
        $mform->addHelpButton('autorefresh', 'idp_autorefresh', 'auth_musaml');

        $options = ['rows' => 8, 'cols' => 70, 'class' => 'text-monospace'];
        $mform->addElement('textarea', 'extracerts', get_string('idp_extracerts', 'auth_musaml'), $options);
        $mform->setType('extracerts', PARAM_RAW);
        $mform->addHelpButton('extracerts', 'idp_extracerts', 'auth_musaml');

        $extra = [];
        foreach ($info['extracerts'] as $cert) {
            $extra[] = idp::to_pem($cert['cert']);
        }
        $this->set_data(['autorefresh' => $info['autorefresh'], 'extracerts' => implode("\n", $extra)]);

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        foreach (idp::split_certificates($data['extracerts'] ?? '') as $cert) {
            if (!\auth_musaml\local\openssl::parse_cert(idp::to_pem($cert))) {
                $errors['extracerts'] = get_string('error_certificate', 'auth_musaml');
                break;
            }
        }
        return $errors;
    }
}
