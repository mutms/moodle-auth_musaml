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

namespace auth_musaml\phpunit\muform\autocomplete;

use auth_musaml\muform\autocomplete\user_mapping_userid;

/**
 * Tests of the user picker of new user mappings.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\muform\autocomplete\user_mapping_userid
 */
final class user_mapping_userid_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_source(): void {
        /** @var \auth_musaml_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $generator->create_idp(['name' => 'Some IDP']);
        $free = $this->getDataGenerator()->create_user(['firstname' => 'Free', 'lastname' => 'User']);
        $mapped = $this->getDataGenerator()->create_user(['firstname' => 'Mapped', 'lastname' => 'User']);
        $generator->create_user_mapping(['idpid' => $idp->id, 'userid' => $mapped->id, 'guid' => 'guid-1']);

        $this->setAdminUser();
        $source = new user_mapping_userid((int)$idp->id);
        $this->assertSame([(int)$idp->id], $source->get_args());

        // Mapped users are never offered.
        $results = $source->search('User', 50);
        $this->assertArrayHasKey((string)$free->id, $results);
        $this->assertArrayNotHasKey((string)$mapped->id, $results);

        // Labels exist for both, validation explains why a mapped user cannot be picked.
        $this->assertNotNull($source->label((string)$free->id));
        $this->assertNotNull($source->label((string)$mapped->id));
        $this->assertNull($source->validate((string)$free->id));
        $this->assertSame(get_string('error_usermapped', 'auth_musaml'), $source->validate((string)$mapped->id));
        $this->assertNull($source->label('999999'));
    }

    public function test_unknown_idp(): void {
        $this->setAdminUser();
        $this->expectException(\core\exception\invalid_parameter_exception::class);
        new user_mapping_userid(999999);
    }

    public function test_capability(): void {
        /** @var \auth_musaml_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $generator->create_idp(['name' => 'Some IDP']);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\required_capability_exception::class);
        new user_mapping_userid((int)$idp->id);
    }
}
