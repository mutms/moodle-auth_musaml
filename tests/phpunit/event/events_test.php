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

namespace auth_musaml\phpunit\event;

use auth_musaml\local\attribute;
use auth_musaml\local\idp;
use auth_musaml\local\login;
use auth_musaml\local\mapping;
use auth_musaml\local\saml;

/**
 * Plugin events test.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\event\idp_created
 * @covers \auth_musaml\event\idp_updated
 * @covers \auth_musaml\event\idp_deleted
 * @covers \auth_musaml\event\login_failed
 * @covers \auth_musaml\event\user_mapping_created
 * @covers \auth_musaml\event\user_mapping_deleted
 * @covers \auth_musaml\event\metadata_refresh_failed
 */
final class events_test extends \advanced_testcase {
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
     * Events of one class from the sink.
     *
     * @param \core\event\base[] $events
     * @param string $class
     * @return \core\event\base[]
     */
    private function filter(array $events, string $class): array {
        return array_values(array_filter($events, fn($event) => $event instanceof $class));
    }

    public function test_idp_events(): void {
        $sink = $this->redirectEvents();
        $idp = $this->get_generator()->create_idp(['name' => 'First']);
        $created = $this->filter($sink->get_events(), \auth_musaml\event\idp_created::class);
        $sink->clear();

        $this->assertCount(1, $created);
        $this->assertEquals($idp->id, $created[0]->objectid);
        $this->assertSame($idp->entityid, $created[0]->other['entityid']);
        $this->assertSame('First', $created[0]->other['name']);
        $this->assertStringContainsString('was created', $created[0]->get_description());
        $this->assertSame('auth_musaml_idp', $created[0]->objecttable);
        $this->assertEventContextNotUsed($created[0]);

        idp::update((object)['id' => $idp->id, 'name' => 'Second']);
        $updated = $this->filter($sink->get_events(), \auth_musaml\event\idp_updated::class);
        $sink->clear();
        $this->assertCount(1, $updated);
        $this->assertSame('Second', $updated[0]->other['name']);

        idp::delete($idp->id);
        $deleted = $this->filter($sink->get_events(), \auth_musaml\event\idp_deleted::class);
        $this->assertCount(1, $deleted);
        $this->assertEquals($idp->id, $deleted[0]->objectid);
        $this->assertStringContainsString('was deleted', $deleted[0]->get_description());
        $sink->close();
    }

    public function test_mapping_events(): void {
        $gen = $this->get_generator();
        $idp = $gen->create_idp();
        $user = $this->getDataGenerator()->create_user(['auth' => 'musaml']);

        $sink = $this->redirectEvents();
        $mapping = mapping::create((object)['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-1']);
        $created = $this->filter($sink->get_events(), \auth_musaml\event\user_mapping_created::class);
        $sink->clear();

        $this->assertCount(1, $created);
        $this->assertEquals($mapping->id, $created[0]->objectid);
        $this->assertEquals($user->id, $created[0]->relateduserid);
        $this->assertSame('z-1', $created[0]->other['guid']);
        $this->assertFalse($created[0]->other['automapped']);
        $this->assertStringContainsString('manually', $created[0]->get_description());

        mapping::delete($mapping->id);
        $deleted = $this->filter($sink->get_events(), \auth_musaml\event\user_mapping_deleted::class);
        $this->assertCount(1, $deleted);
        $this->assertEquals($user->id, $deleted[0]->relateduserid);
        $this->assertStringContainsString('was removed', $deleted[0]->get_description());

        // Deleting an identity provider reports every link it removes.
        $sink->clear();
        mapping::create((object)['idpid' => $idp->id, 'userid' => $user->id, 'guid' => 'z-2']);
        $sink->clear();
        idp::delete($idp->id);
        $this->assertCount(1, $this->filter($sink->get_events(), \auth_musaml\event\user_mapping_deleted::class));
        $sink->close();
    }

    public function test_automatic_mapping_is_marked_as_such(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID', 'automap' => 1]);
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email',
            'sync' => attribute::SYNC_NONE, 'usermapping' => 1]);
        $this->getDataGenerator()->create_user(['auth' => 'musaml', 'email' => 'jane@example.com']);

        $sink = $this->redirectEvents();
        login::resolve_user($idp, 'z-1', ['Email' => ['jane@example.com']]);
        $events = $this->filter($sink->get_events(), \auth_musaml\event\user_mapping_created::class);
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertTrue($events[0]->other['automapped']);
        $this->assertStringContainsString('automatically', $events[0]->get_description());
    }

    public function test_login_failed_event_carries_the_reason(): void {
        saml::regenerate_sp_certificate();
        $gen = $this->get_generator();
        $idp = $gen->create_idp(['mapattr' => 'UserID']);

        $sink = $this->redirectEvents();
        try {
            $user = login::authenticate($idp, ['UserID' => ['z-1']]);
            @login::finish($idp, $user, ['UserID' => ['z-1']], [], '');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertSame('error_nomapping', $e->errorcode);
        }
        $events = $this->filter($sink->get_events(), \auth_musaml\event\login_failed::class);
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertSame('error_nomapping', $events[0]->other['reason']);
        $this->assertSame('z-1', $events[0]->other['detail']);
        $this->assertEquals($idp->id, $events[0]->other['idpid']);
        $this->assertStringContainsString('No user is mapped', $events[0]->get_description());
    }

    public function test_metadata_refresh_failure_is_reported(): void {
        $idp = $this->get_generator()->create_idp(['metadataurl' => 'ftp://nope']);

        $sink = $this->redirectEvents();
        idp::refresh_metadata($idp);
        $events = $this->filter($sink->get_events(), \auth_musaml\event\metadata_refresh_failed::class);
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertEquals($idp->id, $events[0]->objectid);
        $this->assertSame('ftp://nope', $events[0]->other['url']);
        $this->assertNotEmpty($events[0]->other['error']);
        $this->assertStringContainsString('could not be refreshed', $events[0]->get_description());
    }

    /**
     * php-saml Auth instance that reports a validated session without any network.
     *
     * @return \OneLogin\Saml2\Auth
     */
    private function get_fake_auth(): \OneLogin\Saml2\Auth {
        return new class extends \OneLogin\Saml2\Auth {
            /**
             * Constructor without settings validation.
             */
            public function __construct() {
            }

            #[\Override]
            public function getNameId() {
                return 'n';
            }

            #[\Override]
            public function getNameIdFormat() {
                return 'urn:x';
            }

            #[\Override]
            public function getSessionIndex() {
                return 'idx';
            }

            #[\Override]
            public function getAttributes() {
                return [];
            }
        };
    }
}
