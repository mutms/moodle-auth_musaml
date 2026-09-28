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
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\number;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\form;

/**
 * Regenerate SP certificate form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sp_cert_regen extends form {
    #[\Override]
    protected function definition(): void {
        $info = $this->get_extra_data()['info'];
        if ($info) {
            $warning = '<div class="alert alert-warning">' . s(get_string('sp_cert_regen_warning', 'auth_musaml')) . '</div>';
            $this->add(new inforawhtml('warning', '', $warning));
        }

        $entityid = (new text('entityid', get_string('sp_entityid', 'auth_musaml'), ['type' => 'url', 'width' => 'medium']))
            ->set_default(saml::get_sp_entityid())
            ->set_required(true)
            ->add_help_button('sp_entityid', 'auth_musaml');
        $this->add($entityid);

        $dn = saml::get_default_dn();
        $commonname = (new text('commonname', get_string('sp_cert_commonname', 'auth_musaml'), ['width' => 'medium']))
            ->set_default($dn['commonName'])
            ->set_required(true);
        $this->add($commonname);
        $orglabel = get_string('sp_cert_organizationname', 'auth_musaml');
        $organizationname = (new text('organizationname', $orglabel, ['width' => 'medium']))
            ->set_default($dn['organizationName'])
            ->set_required(true);
        $this->add($organizationname);

        $days = (new number('days', get_string('sp_cert_days', 'auth_musaml'), ['min' => 1, 'max' => 36500, 'width' => 'small']))
            ->set_default(openssl::CERT_DAYS)
            ->set_required(true);
        $this->add($days);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string($info ? 'sp_cert_regen' : 'sp_cert_create', 'auth_musaml')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
