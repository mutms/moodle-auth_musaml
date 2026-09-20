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

/**
 * Auth plugin class test.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_plugin_musaml
 */
final class auth_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_plugin_is_sso_only(): void {
        $auth = get_auth_plugin('musaml');

        $this->assertInstanceOf(\auth_plugin_musaml::class, $auth);
        $this->assertSame('musaml', $auth->authtype);
        $this->assertFalse($auth->is_internal());
        $this->assertTrue($auth->prevent_local_passwords());
        $this->assertFalse($auth->can_change_password());
        $this->assertFalse($auth->can_reset_password());
        $this->assertNull($auth->change_password_url());
        $this->assertTrue($auth->can_be_manually_set());
        $this->assertFalse($auth->user_login('admin', 'whatever'));
    }

    public function test_plugin_is_not_configured_without_sp_certificate(): void {
        $auth = get_auth_plugin('musaml');

        $this->assertFalse($auth->is_configured());
        $this->assertSame([], $auth->loginpage_idp_list('/'));
    }

    public function test_tables_are_installed(): void {
        global $DB;

        $dbman = $DB->get_manager();
        $this->assertTrue($dbman->table_exists('auth_musaml_idp'));
        $this->assertTrue($dbman->table_exists('auth_musaml_user'));
        $this->assertTrue($dbman->table_exists('auth_musaml_attribute'));
    }
}
