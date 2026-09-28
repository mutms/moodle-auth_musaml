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
use auth_musaml\muform\autocomplete\user_mapping_userid;
use tool_mulib\muform\element\autocomplete;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\hidden;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\form;

/**
 * Create user mapping form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_create extends form {
    #[\Override]
    protected function definition(): void {
        $idp = $this->get_extra_data()['idp'];

        $this->add(new hidden('idpid'));

        $source = new user_mapping_userid((int)$idp->id);
        $userid = (new autocomplete('userid', get_string('user_mapping_user', 'auth_musaml'), $source))
            ->set_required(true)
            ->add_help_button('user_mapping_user', 'auth_musaml');
        $this->add($userid);

        $guidattributes = ['type' => 'rawtext', 'maxlength' => 255, 'width' => 'medium'];
        $guid = (new text('guid', get_string('user_mapping_guid', 'auth_musaml'), $guidattributes))
            ->set_required(true)
            ->add_help_button('user_mapping_guid', 'auth_musaml');
        $this->add($guid);

        $allowotherauth = (new checkbox('allowotherauth', get_string('user_mapping_allowotherauth', 'auth_musaml')))
            ->add_help_button('user_mapping_allowotherauth', 'auth_musaml');
        $this->add($allowotherauth);

        $setauth = (new checkbox('setauth', get_string('user_mapping_setauth', 'auth_musaml')))
            ->set_default(1)
            ->add_help_button('user_mapping_setauth', 'auth_musaml');
        $this->add($setauth);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('user_mapping_create', 'auth_musaml')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $error = self::validate_guid((string)$data['guid'], (int)$this->get_extra_data()['idp']->id, 0);
        if ($error !== null) {
            $allerrors['guid'][] = $error;
        }
    }

    /**
     * Validate the identity provider account ID.
     *
     * @param string $guid
     * @param int $idpid
     * @param int $mappingid current mapping, 0 for a new one
     * @return string|null error text
     */
    private static function validate_guid(string $guid, int $idpid, int $mappingid): ?string {
        $guid = trim($guid);
        if ($guid === '') {
            return null;
        }
        if (\core_text::strlen($guid) > 255) {
            return get_string('error');
        }
        $other = mapping::fetch_by_guid($idpid, $guid);
        if ($other && $other->id != $mappingid) {
            return get_string('error_guidmapped', 'auth_musaml');
        }
        return null;
    }
}
