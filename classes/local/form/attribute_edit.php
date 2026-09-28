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
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\hidden;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\form;

/**
 * Create and update form for attribute mappings.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attribute_edit extends form {
    #[\Override]
    protected function definition(): void {
        $extra = $this->get_extra_data();
        $idp = $extra['idp'];
        $isupdate = array_key_exists('id', $this->get_current_data());

        $this->add(new hidden('idpid'));
        if ($isupdate) {
            $this->add(new hidden('id'));
        }

        // Without a placeholder the first field looks preselected and everything becomes a username.
        $menu = ['' => get_string('choosedots')] + userfield::get_menu();
        $userfield = (new select('userfield', get_string('attribute_userfield', 'auth_musaml'), $menu))
            ->set_required(true)
            ->add_help_button('attribute_userfield', 'auth_musaml');
        $this->add($userfield);

        $idpattr = (new text('idpattr', get_string('attribute_idpattr', 'auth_musaml'), ['type' => 'rawtext', 'width' => 'medium']))
            ->set_required(true)
            ->add_help_button('attribute_idpattr', 'auth_musaml');
        if (!$isupdate && !empty($extra['idpattr'])) {
            $idpattr->set_default($extra['idpattr']);
        }
        $this->add($idpattr);

        $advertised = idp::get_certinfo($idp)['attributes'];
        if ($advertised) {
            $hint = get_string('attribute_advertised', 'auth_musaml', implode(', ', $advertised));
            $this->add(new inforawhtml('advertised', '', s($hint)));
        }

        $sync = (new select('sync', get_string('attribute_sync', 'auth_musaml'), attribute::get_sync_menu()))
            ->set_default(attribute::SYNC_ONLOGIN)
            ->add_help_button('attribute_sync', 'auth_musaml');
        $this->add($sync);

        $usermapping = (new checkbox('usermapping', get_string('attribute_usermapping', 'auth_musaml')))
            ->add_help_button('attribute_usermapping', 'auth_musaml');
        $this->add($usermapping);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string($isupdate ? 'attribute_update' : 'attribute_create', 'auth_musaml')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $idp = $this->get_extra_data()['idp'];
        $attributeid = (int)($this->get_current_data()['id'] ?? 0);

        if ($data['userfield'] !== null) {
            if (!array_key_exists($data['userfield'], userfield::get_menu())) {
                $allerrors['userfield'][] = get_string('required');
            } else if (attribute::userfield_exists($idp->id, $data['userfield'], $attributeid)) {
                $allerrors['userfield'][] = get_string('error_userfieldmapped', 'auth_musaml');
            }
        }
        if ($data['userfield'] === 'username' && (int)$data['sync'] === attribute::SYNC_ONLOGIN) {
            $allerrors['sync'][] = get_string('error_usernamesync', 'auth_musaml');
        }
    }
}
