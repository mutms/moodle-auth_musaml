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

// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace auth_musaml\phpunit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Environment checks test.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('auth_musaml_environment_saml2')]
#[Group('MuTMS')]
final class environmentlib_test extends \advanced_testcase {
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->libdir . '/environmentlib.php');
        require_once($CFG->dirroot . '/auth/musaml/environmentlib.php');
        parent::setUpBeforeClass();
    }

    public function test_environment_saml2(): void {
        $result = auth_musaml_environment_saml2(new \environment_results('custom_check'));
        $installed = \core\component::get_plugin_directory('auth', 'saml2') !== null;
        $this->assertSame(!$installed, $result->getStatus());
    }

    public function test_environment_xml(): void {
        global $CFG;
        [, $results] = check_moodle_environment($CFG->release, ENV_SELECT_RELEASE);
        $found = array_filter(
            $results,
            fn($r) => $r->getPart() === 'custom_check' && $r->getInfo() === 'auth_saml2 plugin is not present'
        );
        $this->assertCount(1, $found);
        $result = reset($found);
        $this->assertSame('optional', $result->getLevel());
        $this->assertSame(\core\component::get_plugin_directory('auth', 'saml2') === null, $result->getStatus());
    }
}
