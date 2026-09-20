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

use auth_musaml\local\assertion;

/**
 * Replay protection test.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\assertion
 */
final class assertion_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_register_detects_replay_and_cleans_up(): void {
        global $DB;
        $gen = $this->getDataGenerator()->get_plugin_generator('auth_musaml');
        $idp = $gen->create_idp();
        $other = $gen->create_idp();

        $this->assertTrue(assertion::register($idp->id, '_abc', time() + 300));
        $this->assertFalse(assertion::register($idp->id, '_abc', time() + 300), 'same id is a replay');
        $this->assertFalse(assertion::register($other->id, '_abc', time() + 300), 'ids are unique across IDPs');
        $this->assertTrue(assertion::register($idp->id, '_def', null));

        $rows = $DB->get_records('auth_musaml_assertion', [], 'id ASC');
        $this->assertCount(2, $rows);
        [$abc, $def] = array_values($rows);
        $this->assertEqualsWithDelta(time() + 300 + assertion::CLOCK_SKEW, $abc->timeexpires, 5);
        $this->assertEqualsWithDelta(time() + assertion::DEFAULT_TTL + assertion::CLOCK_SKEW, $def->timeexpires, 5);

        // Expired rows are removed on the next insert and their ids become usable again.
        $DB->set_field('auth_musaml_assertion', 'timeexpires', time() - 1, ['assertionid' => '_abc']);
        $this->assertTrue(assertion::register($idp->id, '_ghi', time() + 10));
        $this->assertFalse($DB->record_exists('auth_musaml_assertion', ['assertionid' => '_abc']));
        $this->assertTrue(assertion::register($idp->id, '_abc', time() + 10));

        $this->assertFalse(assertion::register($idp->id, '', time() + 10));
        $this->assertFalse(assertion::register($idp->id, str_repeat('x', 256), time() + 10));
    }
}
