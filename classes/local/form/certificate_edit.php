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
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\hidden;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;

/**
 * Identity provider certificate handling form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certificate_edit extends form {
    #[\Override]
    protected function definition(): void {
        $this->add(new hidden('id'));

        $autorefresh = (new checkbox('autorefresh', get_string('idp_autorefresh', 'auth_musaml')))
            ->add_help_button('idp_autorefresh', 'auth_musaml');
        $this->add($autorefresh);

        $extracerts = (new textarea('extracerts', get_string('idp_extracerts', 'auth_musaml'), ['type' => 'rawtext', 'rows' => 8]))
            ->add_help_button('idp_extracerts', 'auth_musaml');
        $this->add($extracerts);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('savechanges')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        foreach (idp::split_certificates($data['extracerts'] ?? '') as $cert) {
            if (!\auth_musaml\local\openssl::parse_cert(idp::to_pem($cert))) {
                $allerrors['extracerts'][] = get_string('error_certificate', 'auth_musaml');
                break;
            }
        }
    }
}
