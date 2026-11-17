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

namespace auth_musaml\phpunit\muform\userpicker;

use auth_musaml\muform\userpicker\user_mapping_userid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests of the user picker of new user mappings.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\auth_musaml\muform\userpicker\user_mapping_userid::class)]
#[Group('MuTMS')]
final class user_mapping_userid_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Users on the first page of the panel.
     *
     * @param \tool_mulib\muform\userpicker\base $source
     * @param string $query
     * @param string[] $exclude
     * @return array user id => label html
     */
    private function search(\tool_mulib\muform\userpicker\base $source, string $query, array $exclude = []): array {
        $result = $source->search($query, $exclude, 0, []);
        return array_combine(array_column($result['rows'], 'id'), array_column($result['rows'], 'label'));
    }

    public function test_source(): void {
        /** @var \auth_musaml_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $generator->create_idp(['name' => 'Some IDP']);
        $free = $this->getDataGenerator()->create_user(['firstname' => 'Free', 'lastname' => 'User']);
        $mapped = $this->getDataGenerator()->create_user(['firstname' => 'Mapped', 'lastname' => 'User']);
        $suspended = $this->getDataGenerator()->create_user(['firstname' => 'Suspended', 'lastname' => 'User', 'suspended' => 1]);
        $generator->create_user_mapping(['idpid' => $idp->id, 'userid' => $mapped->id, 'guid' => 'guid-1']);

        $this->setAdminUser();
        $source = new user_mapping_userid((int)$idp->id);
        $this->assertSame([(int)$idp->id], $source->get_args());

        // Mapped users are never offered.
        $results = $this->search($source, 'User');
        $this->assertArrayHasKey((string)$free->id, $results);
        $this->assertArrayNotHasKey((string)$mapped->id, $results);

        // Labels exist for both, validation explains why a mapped user cannot be picked.
        $value = (string)$free->id;
        $this->assertNotNull($source->labels([$value])[$value] ?? null);
        $value = (string)$mapped->id;
        $this->assertNotNull($source->labels([$value])[$value] ?? null);
        $value = (string)$free->id;
        $this->assertNull($source->validate([$value])[$value] ?? null);
        $value = (string)$mapped->id;
        $this->assertSame(get_string('error_usermapped', 'auth_musaml'), $source->validate([$value])[$value] ?? null);
        $this->assertNull($source->labels(['999999'])['999999'] ?? null);

        // Suspended users may be mapped too.
        $this->assertArrayHasKey((string)$suspended->id, $results);
        $value = (string)$suspended->id;
        $this->assertNull($source->validate([$value])[$value] ?? null);
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
