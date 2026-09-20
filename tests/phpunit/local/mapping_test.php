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

use auth_musaml\local\mapping;

/**
 * User mapping test.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\mapping
 */
final class mapping_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create_fetch_delete(): void {
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $other = $gen->create_idp();
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml']);
        $user2 = $this->getDataGenerator()->create_user();

        $mapping = mapping::create((object)['idpid' => $idp->id, 'userid' => $user->id, 'guid' => ' abc ']);
        $this->assertSame('abc', $mapping->guid);
        $this->assertSame('0', $mapping->allowotherauth);
        $this->assertSame('0', $mapping->automapped);
        $this->assertEqualsWithDelta(time(), $mapping->timecreated, 5);

        $this->assertEquals($mapping, mapping::fetch_by_guid($idp->id, 'abc'));
        $this->assertEquals($mapping, mapping::fetch_by_userid($user->id));
        $this->assertNull(mapping::fetch_by_guid($other->id, 'abc'));
        $this->assertNull(mapping::fetch_by_guid($idp->id, 'ABC'), 'guid is case sensitive');
        $this->assertNull(mapping::fetch_by_userid($user2->id));

        try {
            mapping::create((object)['idpid' => $other->id, 'userid' => $user->id, 'guid' => 'x']);
            $this->fail('user may be mapped only once');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame('error_usermapped', $e->errorcode);
        }
        try {
            mapping::create((object)['idpid' => $idp->id, 'userid' => $user2->id, 'guid' => 'abc']);
            $this->fail('guid may be mapped only once per IDP');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame('error_guidmapped', $e->errorcode);
        }
        $second = mapping::create((object)['idpid' => $other->id, 'userid' => $user2->id, 'guid' => 'abc', 'allowotherauth' => 1]);
        $this->assertSame('1', $second->allowotherauth);

        mapping::delete($mapping->id);
        $this->assertNull(mapping::fetch_by_userid($user->id));

        $generated = $gen->create_user_mapping(['idp' => $idp->name, 'user' => 'newone']);
        $this->assertSame('musaml', \core\user::get_user($generated->userid)->auth);
        $this->assertSame('guid-' . $generated->userid, $generated->guid);
    }

    public function test_update_and_set_auth(): void {
        global $DB;
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $user = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $other = $this->getDataGenerator()->create_user(['auth' => 'musaml']);
        $mapping = mapping::create((object)['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'a']);
        $othermapping = mapping::create((object)['idpid' => $idp->id, 'userid' => $other->id, 'guid' => 'b']);

        $updated = mapping::update((object)['id' => $mapping->id, 'guid' => ' c ', 'allowotherauth' => 1]);
        $this->assertSame('c', $updated->guid);
        $this->assertSame('1', $updated->allowotherauth);
        $this->assertEquals($user->id, $updated->userid, 'the mapped user never changes');

        try {
            mapping::update((object)['id' => $mapping->id, 'guid' => 'b']);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame('error_guidmapped', $e->errorcode);
        }
        try {
            mapping::update((object)['id' => $mapping->id, 'guid' => '']);
            $this->fail('exception expected');
        } catch (\core\exception\coding_exception $e) {
            $this->assertStringContainsString('guid', $e->getMessage());
        }
        $this->assertNotNull($othermapping);

        // Switching the user account to SAML authentication.
        $sink = $this->redirectEvents();
        mapping::set_user_auth($user->id);
        $this->assertSame('musaml', $DB->get_field('user', 'auth', ['id' => $user->id]));
        $this->assertCount(1, array_filter($sink->get_events(), fn($e) => $e instanceof \core\event\user_updated));

        mapping::set_user_auth($other->id);
        $this->assertSame('musaml', $DB->get_field('user', 'auth', ['id' => $other->id]));
        $sink->close();
    }

    public function test_create_rejects_invalid_input(): void {
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $user = $this->getDataGenerator()->create_user();
        $this->expectException(\core\exception\coding_exception::class);
        mapping::create((object)['idpid' => $idp->id, 'userid' => $user->id, 'guid' => '']);
    }
}
