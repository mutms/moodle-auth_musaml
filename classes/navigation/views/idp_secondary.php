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

namespace auth_musaml\navigation\views;

use core\navigation\navigation_node;
use core\url;
use moodle_page;
use stdClass;

/**
 * IDP page tabs.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp_secondary extends \core\navigation\views\secondary {
    /** @var stdClass */
    private stdClass $idp;

    /**
     * Constructor.
     *
     * @param moodle_page $page
     * @param stdClass $idp
     */
    public function __construct(moodle_page $page, stdClass $idp) {
        parent::__construct($page);
        $this->idp = $idp;
    }

    #[\Override]
    public function initialise(): void {
        $this->id = 'secondary_navigation';
        $this->headertitle = get_string('menu');
        $id = $this->idp->id;

        $url = new url('/auth/musaml/management/idp.php', ['id' => $id]);
        $this->add(get_string('tab_details', 'auth_musaml'), $url, navigation_node::TYPE_SETTING, null, 'idp_details');

        $url = new url('/auth/musaml/management/attributes.php', ['id' => $id]);
        $this->add(get_string('tab_attributes', 'auth_musaml'), $url, navigation_node::TYPE_SETTING, null, 'idp_attributes');

        $url = new url('/auth/musaml/management/users.php', ['id' => $id]);
        $this->add(get_string('tab_mappings', 'auth_musaml'), $url, navigation_node::TYPE_SETTING, null, 'idp_mappings');

        $this->scan_for_active_node($this);
        $this->initialised = true;
    }
}
