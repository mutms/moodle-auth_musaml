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

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\hidden;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Delete attribute mapping confirmation form.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attribute_delete extends form {
    #[\Override]
    protected function definition(): void {
        $info = '<div class="alert alert-warning">' . s(get_string('attribute_delete_info', 'auth_musaml')) . '</div>';
        $this->add(new inforawhtml('deleteinfo', '', $info));
        $this->add(new info('userfield', get_string('attribute_userfield', 'auth_musaml'), info::PLAIN));
        $this->add(new info('idpattr', get_string('attribute_idpattr', 'auth_musaml'), info::PLAIN));
        $this->add(new hidden('id'));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('attribute_delete', 'auth_musaml')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
