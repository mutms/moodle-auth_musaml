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

namespace auth_musaml\phpunit\local;

use auth_musaml\local\userfield;

/**
 * User field and locking test.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\userfield
 */
final class userfield_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Add a text profile field.
     *
     * @param string $shortname
     */
    private function create_profile_field(string $shortname): void {
        global $DB;
        $DB->insert_record('user_info_field', (object)['shortname' => $shortname, 'name' => ucfirst($shortname),
            'categoryid' => 1, 'datatype' => 'text', 'sortorder' => 1, 'required' => 0, 'locked' => 0,
            'visible' => 2, 'forceunique' => 0, 'signup' => 0, 'defaultdata' => '', 'param1' => 30, 'param2' => 255]);
    }

    public function test_profile_field_helpers(): void {
        $this->assertTrue(userfield::is_profile_field('profile_staffid'));
        $this->assertFalse(userfield::is_profile_field('email'));
        $this->assertSame('staffid', userfield::get_profile_shortname('profile_staffid'));
        $this->assertContains('username', userfield::get_core_fields());
        $this->assertSame(['username', 'email', 'firstname', 'lastname'], userfield::get_required_fields());
    }

    public function test_core_field_locks(): void {
        $this->create_profile_field('staffid');

        // A new site locks the fields identity providers own.
        $this->assertSame(['firstname', 'lastname', 'email'], userfield::get_default_locked());
        foreach (userfield::get_default_locked() as $field) {
            $this->assertSame('locked', get_config('auth_musaml', 'field_lock_' . $field), $field);
        }
        $this->assertSame('unlocked', get_config('auth_musaml', 'field_lock_city'));

        $auth = get_auth_plugin('musaml');
        $this->assertSame('locked', $auth->config->field_lock_email);
        $this->assertSame('unlocked', $auth->config->field_lock_city);

        set_config('field_lock_email', 'unlocked', 'auth_musaml');
        set_config('field_lock_profile_field_staffid', 'locked', 'auth_musaml');
        $auth = get_auth_plugin('musaml');

        $this->assertSame('unlocked', $auth->config->field_lock_email, 'admins may unlock a field');
        $this->assertSame('locked', $auth->config->field_lock_profile_field_staffid);
        $this->assertSame('locked', $auth->config->field_lock_lastname);
        $this->assertContains('profile_field_staffid', $auth->get_custom_user_profile_fields());
        $this->assertContains('email', $auth->userfields);
    }

    public function test_userfield_menu(): void {
        $this->create_profile_field('staffid');
        $menu = userfield::get_menu();

        $this->assertArrayHasKey('username', $menu);
        $this->assertArrayHasKey('email', $menu);
        $this->assertArrayHasKey('profile_staffid', $menu);
        $this->assertArrayNotHasKey('id', $menu);
        $this->assertArrayNotHasKey('auth', $menu);
        $this->assertArrayNotHasKey('password', $menu);
        $this->assertSame('Staffid', $menu['profile_staffid']);
    }
}
