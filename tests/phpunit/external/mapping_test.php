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

namespace auth_musaml\phpunit\external;

use auth_musaml\external\create_user_mapping;
use auth_musaml\external\delete_user_mapping;
use auth_musaml\local\mapping;

/**
 * Mapping web services test.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\external\create_user_mapping
 * @covers \auth_musaml\external\delete_user_mapping
 */
final class mapping_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * A user holding only the mapping capability, as an external system would.
     *
     * @return \stdClass
     */
    private function create_manager(): \stdClass {
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('auth/musaml:managemappings', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        return $user;
    }

    public function test_create_and_delete(): void {
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $user = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $this->setUser($this->create_manager());

        $result = create_user_mapping::execute($idp->entityid, 'z-1', $user->id);
        $result = \core_external\external_api::clean_returnvalue(create_user_mapping::execute_returns(), $result);

        $this->assertEquals($user->id, $result['userid']);
        $this->assertSame('z-1', $result['guid']);
        $this->assertFalse($result['allowotherauth']);
        $this->assertEqualsWithDelta(time(), $result['timecreated'], 10);
        $this->assertNotNull(mapping::fetch_by_guid($idp->id, 'z-1'));
        $this->assertSame('manual', \core\user::get_user($user->id)->auth, 'the method is kept by default');

        $deleted = delete_user_mapping::execute($idp->entityid, 'z-1');
        $this->assertTrue(\core_external\external_api::clean_returnvalue(delete_user_mapping::execute_returns(), $deleted));
        $this->assertNull(mapping::fetch_by_guid($idp->id, 'z-1'));

        // Deleting something that is not there is not an error.
        $this->assertFalse(delete_user_mapping::execute($idp->entityid, 'z-1'));
    }

    public function test_create_options(): void {
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $user = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $this->setUser($this->create_manager());

        $result = create_user_mapping::execute($idp->entityid, 'z-1', $user->id, true, true);

        $this->assertTrue($result['allowotherauth']);
        $this->assertSame('musaml', \core\user::get_user($user->id)->auth);
    }

    public function test_capability_is_required(): void {
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($this->getDataGenerator()->create_user());
        try {
            create_user_mapping::execute($idp->entityid, 'z-1', $user->id);
            $this->fail('exception expected');
        } catch (\core\exception\required_capability_exception $e) {
            $this->assertStringContainsString('do not currently have permissions', $e->getMessage());
        }
        try {
            delete_user_mapping::execute($idp->entityid, 'z-1');
            $this->fail('exception expected');
        } catch (\core\exception\required_capability_exception $e) {
            $this->assertNotNull($e);
        }
    }

    public function test_management_pages_use_the_capability(): void {
        // The pages check the same capability as the services, so mapping managers
        // do not need site administration rights.
        $context = \context_system::instance();
        $manager = $this->create_manager();

        $this->setUser($manager);
        $this->assertTrue(has_capability('auth/musaml:managemappings', $context));
        $this->assertFalse(has_capability('moodle/site:config', $context));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(has_capability('auth/musaml:managemappings', $context));

        $this->setAdminUser();
        $this->assertTrue(has_capability('auth/musaml:managemappings', $context), 'administrators keep access');
    }

    public function test_invalid_input(): void {
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($this->create_manager());

        try {
            create_user_mapping::execute('https://unknown.example.com', 'z-1', $user->id);
            $this->fail('exception expected');
        } catch (\core\exception\invalid_parameter_exception $e) {
            $this->assertStringContainsString('Unknown identity provider', $e->getMessage());
        }
        try {
            create_user_mapping::execute($idp->entityid, 'z-1', -1);
            $this->fail('exception expected');
        } catch (\core\exception\invalid_parameter_exception $e) {
            $this->assertStringContainsString('Unknown user', $e->getMessage());
        }

        // The same Moodle user and the same identity provider account may be mapped once.
        create_user_mapping::execute($idp->entityid, 'z-1', $user->id);
        $second = $this->getDataGenerator()->create_user();
        try {
            create_user_mapping::execute($idp->entityid, 'z-1', $second->id);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame('error_guidmapped', $e->errorcode);
        }
    }

    public function test_site_administrators_are_protected(): void {
        global $DB;
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $admin = $this->getDataGenerator()->create_user();
        set_config('siteadmins', get_config('core', 'siteadmins') . ',' . $admin->id);
        $this->assertTrue(is_siteadmin($admin->id));

        $this->setUser($this->create_manager());
        try {
            create_user_mapping::execute($idp->entityid, 'z-1', $admin->id);
            $this->fail('exception expected');
        } catch (\core\exception\invalid_parameter_exception $e) {
            $this->assertStringContainsString('Site administrators cannot be mapped', $e->getMessage());
        }

        // Site administrators may still do it themselves.
        $this->setAdminUser();
        $result = create_user_mapping::execute($idp->entityid, 'z-1', $admin->id);
        $this->assertEquals($admin->id, $result['userid']);
    }
}
