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

use auth_musaml\local\attribute;

/**
 * Attribute synchronisation and lock test.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\attribute
 */
final class attribute_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Plugin generator.
     *
     * @return \auth_musaml_generator
     */
    private function get_generator(): \auth_musaml_generator {
        return $this->getDataGenerator()->get_plugin_generator('auth_musaml');
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

    public function test_crud(): void {
        $gen = $this->get_generator();
        $idp = $gen->create_idp();
        $other = $gen->create_idp();

        $attribute = attribute::create((object)['idpid' => $idp->id, 'idpattr' => ' Email ',
            'userfield' => 'email', 'sync' => attribute::SYNC_ONLOGIN, 'usermapping' => 1]);
        $this->assertSame('Email', $attribute->idpattr);
        $this->assertSame('email', $attribute->userfield);
        $this->assertSame((string)attribute::SYNC_ONLOGIN, $attribute->sync);
        $this->assertSame('1', $attribute->usermapping);
        $this->assertEquals($attribute, attribute::fetch($attribute->id));
        $this->assertNull(attribute::fetch(-1));

        $updated = attribute::update((object)['id' => $attribute->id, 'idpattr' => 'Mail',
            'sync' => attribute::SYNC_ONCREATE, 'usermapping' => 0]);
        $this->assertSame('Mail', $updated->idpattr);
        $this->assertSame((string)attribute::SYNC_ONCREATE, $updated->sync);
        $this->assertSame('0', $updated->usermapping);
        $this->assertSame('email', $updated->userfield, 'untouched fields stay');

        // One user field per IDP, other IDPs are independent.
        $this->assertTrue(attribute::userfield_exists($idp->id, 'email'));
        $this->assertFalse(attribute::userfield_exists($idp->id, 'email', $attribute->id));
        $this->assertFalse(attribute::userfield_exists($idp->id, 'firstname'));
        $this->assertFalse(attribute::userfield_exists($other->id, 'email'));

        attribute::delete($attribute->id);
        $this->assertNull(attribute::fetch($attribute->id));
    }

    public function test_create_rejects_invalid_input(): void {
        $idp = $this->get_generator()->create_idp();

        try {
            attribute::create((object)['idpid' => $idp->id, 'idpattr' => '', 'userfield' => 'email']);
            $this->fail('exception expected');
        } catch (\core\exception\coding_exception $e) {
            $this->assertStringContainsString('required', $e->getMessage());
        }
        try {
            attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email', 'sync' => 9]);
            $this->fail('exception expected');
        } catch (\core\exception\coding_exception $e) {
            $this->assertStringContainsString('sync', $e->getMessage());
        }
        $attribute = attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email']);
        $this->expectException(\core\exception\coding_exception::class);
        attribute::update((object)['id' => $attribute->id, 'idpattr' => '']);
    }

    public function test_get_value_normalises_username_and_email(): void {
        $idp = $this->get_generator()->create_idp(['usernameprefix' => 'zit']);
        $username = (object)['idpattr' => 'UserName', 'userfield' => 'username'];
        $email = (object)['idpattr' => 'Email', 'userfield' => 'email'];
        $first = (object)['idpattr' => 'FirstName', 'userfield' => 'firstname'];

        $bag = ['UserName' => [' Jane '], 'Email' => ['Jane@Example.COM'], 'FirstName' => ['Jane'], 'Empty' => ['  ']];
        $this->assertSame('zitjane', attribute::get_value($username, $bag, $idp));
        $this->assertSame('jane@example.com', attribute::get_value($email, $bag, $idp));
        $this->assertSame('Jane', attribute::get_value($first, $bag, $idp), 'other fields keep their case');
        $empty = (object)['idpattr' => 'Empty', 'userfield' => 'firstname'];
        $missing = (object)['idpattr' => 'Missing', 'userfield' => 'firstname'];
        $this->assertNull(attribute::get_value($empty, $bag, $idp));
        $this->assertNull(attribute::get_value($missing, $bag, $idp));
    }

    public function test_get_values_respects_sync_level(): void {
        $idp = $this->get_generator()->create_idp();
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'A', 'userfield' => 'firstname',
            'sync' => attribute::SYNC_NONE]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'B', 'userfield' => 'lastname',
            'sync' => attribute::SYNC_ONCREATE]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'C', 'userfield' => 'email',
            'sync' => attribute::SYNC_ONLOGIN]);
        $bag = ['A' => ['a'], 'B' => ['b'], 'C' => ['c@example.com']];

        $oncreate = attribute::get_values($idp, $bag, attribute::SYNC_ONCREATE);
        $this->assertSame(['email' => 'c@example.com', 'lastname' => 'b'], $oncreate, 'ordered by user field');
        $this->assertSame(['email' => 'c@example.com'], attribute::get_values($idp, $bag, attribute::SYNC_ONLOGIN));
    }

    public function test_sync_user(): void {
        global $DB;
        $this->create_profile_field('staffid');
        $idp = $this->get_generator()->create_idp(['usernameprefix' => 'zit']);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'UserName', 'userfield' => 'username',
            'sync' => attribute::SYNC_ONLOGIN]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email',
            'sync' => attribute::SYNC_ONLOGIN]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'FirstName', 'userfield' => 'firstname',
            'sync' => attribute::SYNC_ONCREATE]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'StaffId', 'userfield' => 'profile_staffid',
            'sync' => attribute::SYNC_ONLOGIN]);

        $user = $this->getDataGenerator()->create_user([
            'auth' => 'musaml', 'username' => 'old', 'email' => 'old@example.com',
            'firstname' => 'Old', 'profile_field_staffid' => 'S-1']);
        $bag = ['UserName' => ['new'], 'Email' => ['New@Example.com'], 'FirstName' => ['New'], 'StaffId' => ['S-2']];

        $sink = $this->redirectEvents();
        $this->assertTrue(attribute::sync_user($idp, $user, $bag, attribute::SYNC_ONLOGIN));

        $stored = $DB->get_record('user', ['id' => $user->id]);
        $this->assertSame('old', $stored->username, 'username is never changed');
        $this->assertSame('new@example.com', $stored->email);
        $this->assertSame('Old', $stored->firstname, 'on creation only fields are not touched');
        $this->assertSame('S-2', profile_user_record($user->id, false)->staffid);
        $this->assertCount(1, array_filter($sink->get_events(), fn($e) => $e instanceof \core\event\user_updated));
        $sink->close();

        // Nothing to do on the second run.
        $stored = $DB->get_record('user', ['id' => $stored->id]);
        $this->assertFalse(attribute::sync_user($idp, $stored, $bag, attribute::SYNC_ONLOGIN));

        // Invalid email is skipped instead of breaking the login.
        $bag['Email'] = ['not an email'];
        $this->assertFalse(attribute::sync_user($idp, $stored, $bag, attribute::SYNC_ONLOGIN));
        $this->assertSame('new@example.com', $DB->get_field('user', 'email', ['id' => $stored->id]));
    }

    public function test_is_synchronised_on_login(): void {
        $idp = $this->get_generator()->create_idp();
        $auth = get_auth_plugin('musaml');

        $this->assertFalse(attribute::is_synchronised_on_login());
        $this->assertFalse($auth->is_synchronised_with_external());

        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'A', 'userfield' => 'firstname',
            'sync' => attribute::SYNC_ONCREATE]);
        $this->assertFalse(attribute::is_synchronised_on_login());

        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'B', 'userfield' => 'lastname',
            'sync' => attribute::SYNC_ONLOGIN]);
        $this->assertTrue(attribute::is_synchronised_on_login());
        $this->assertTrue(get_auth_plugin('musaml')->is_synchronised_with_external());
    }
}
