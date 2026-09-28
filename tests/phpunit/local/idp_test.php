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
use auth_musaml\local\idp;
use auth_musaml\local\mapping;
use auth_musaml\local\saml;
use core\exception\moodle_exception;

/**
 * IDP helper test.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\idp
 * @covers \auth_musaml\local\attribute
 * @covers \auth_musaml\local\mapping
 */
final class idp_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Loads the generator class for the static fixture helper.
        $this->get_generator();
    }

    /**
     * @return \auth_musaml_generator
     */
    private function get_generator(): \auth_musaml_generator {
        return $this->getDataGenerator()->get_plugin_generator('auth_musaml');
    }

    public function test_create_from_metadata(): void {
        $metadata = \auth_musaml_generator::get_fixture_metadata();

        $idp = idp::create((object)[
            'provider' => 'zitadel',
            'metadataurl' => 'https://idp.example.com/metadata',
            'name' => ' Zitadel ',
            'enabled' => 1,
            'mapattr' => 'UserID',
            'usernameprefix' => 'ZIT',
            'metadata' => $metadata,
            'attributes' => [['Email', 'email', attribute::SYNC_ONLOGIN, 0]],
        ]);

        $this->assertSame('zitadel', $idp->provider);
        $this->assertSame('Zitadel', $idp->name);
        $this->assertSame('1', $idp->enabled);
        $this->assertSame('zit', $idp->usernameprefix);
        $this->assertNull($idp->tenantid);
        $this->assertNull($idp->customsettingsjson);
        $this->assertSame('https://zitadel.example.com/saml/v2/metadata', $idp->entityid);
        $this->assertSame('https://zitadel.example.com/saml/v2/SSO', $idp->ssourl);
        $this->assertSame('https://zitadel.example.com/saml/v2/SLO', $idp->logouturl);

        $info = idp::get_certinfo($idp);
        $this->assertCount(1, $info['certs']);
        $this->assertNotEmpty($info['certs'][0]['fingerprint']);
        $this->assertGreaterThan(time(), $info['certs'][0]['notafter']);
        $this->assertStringContainsString('ZITADEL', $info['certs'][0]['subject']);
        $this->assertSame('urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', $info['nameidformat']);
        $this->assertSame(['Email', 'SurName', 'FirstName', 'FullName', 'UserName', 'UserID'], $info['attributes']);
        $this->assertNotNull($info['validuntil']);
        $this->assertEqualsWithDelta(time(), $info['fetched'], 10);
        $this->assertNull($info['error']);
        $this->assertSame(1, $info['autorefresh']);

        $attributes = array_values(attribute::get_for_idp($idp->id));
        $this->assertCount(1, $attributes);
        $this->assertSame('Email', $attributes[0]->idpattr);
        $this->assertSame('email', $attributes[0]->userfield);
        $this->assertSame((string)attribute::SYNC_ONLOGIN, $attributes[0]->sync);
        $this->assertSame('0', $attributes[0]->usermapping);

        $this->assertEquals($idp, idp::fetch($idp->id));
        $this->assertEquals($idp, idp::fetch_by_entityid($idp->entityid));
        $this->assertNull(idp::fetch(-1));
    }

    public function test_unknown_provider_falls_back_to_generic(): void {
        $idp = $this->get_generator()->create_idp(['provider' => 'doesnotexist']);
        $this->assertSame('generic', $idp->provider);
    }

    public function test_entityid_must_be_unique(): void {
        $this->get_generator()->create_idp(['entityid' => 'https://idp.example.com']);
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('https://idp.example.com');
        $this->get_generator()->create_idp(['entityid' => 'https://idp.example.com']);
    }

    public function test_update(): void {
        $idp = $this->get_generator()->create_idp(['name' => 'Old']);
        $otheridp = $this->get_generator()->create_idp(['entityid' => 'https://other.example.com']);

        $updated = idp::update((object)[
            'id' => $idp->id,
            'name' => 'New',
            'enabled' => 0,
            'provider' => 'zitadel',
            'customsettingsjson' => ' {"strict": false} ',
            'usernameprefix' => 'ABC',
        ]);
        $this->assertSame('New', $updated->name);
        $this->assertSame('0', $updated->enabled);
        $this->assertSame('zitadel', $updated->provider);
        $this->assertSame('{"strict": false}', $updated->customsettingsjson);
        $this->assertSame('abc', $updated->usernameprefix);
        $this->assertSame($idp->entityid, $updated->entityid);

        $updated = idp::update((object)['id' => $idp->id, 'customsettingsjson' => '']);
        $this->assertNull($updated->customsettingsjson);

        // New metadata replaces endpoints and certificates.
        $metadata = \auth_musaml_generator::get_fixture_metadata();
        $metadata['entityid'] = 'https://new.example.com';
        $metadata['ssourl'] = 'https://new.example.com/sso';
        $updated = idp::update((object)['id' => $idp->id, 'metadataurl' => 'https://new.example.com/md', 'metadata' => $metadata]);
        $this->assertSame('https://new.example.com', $updated->entityid);
        $this->assertSame('https://new.example.com/sso', $updated->ssourl);
        $this->assertSame('https://new.example.com/md', $updated->metadataurl);

        // Cannot steal the entity ID of another IDP.
        $metadata['entityid'] = $otheridp->entityid;
        $this->expectException(moodle_exception::class);
        idp::update((object)['id' => $idp->id, 'metadata' => $metadata]);
    }

    public function test_delete_removes_attributes_and_mappings(): void {
        global $DB;
        $idp = $this->get_generator()->create_idp();
        $otheridp = $this->get_generator()->create_idp();
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        attribute::create((object)['idpid' => $idp->id, 'idpattr' => 'Email', 'userfield' => 'email']);
        attribute::create((object)['idpid' => $otheridp->id, 'idpattr' => 'Email', 'userfield' => 'email']);
        $DB->insert_record('auth_musaml_user', ['idpid' => $idp->id, 'guid' => 'a', 'userid' => $user->id,
            'automapped' => 0, 'timecreated' => time()]);
        $DB->insert_record('auth_musaml_user', ['idpid' => $otheridp->id, 'guid' => 'b', 'userid' => $user2->id,
            'automapped' => 1, 'timecreated' => time()]);
        $this->assertSame(1, mapping::count_for_idp($idp->id));
        $mappings = array_values(mapping::get_for_idp($otheridp->id));
        $this->assertSame($user2->username, $mappings[0]->username);

        idp::delete($idp->id);

        $this->assertNull(idp::fetch($idp->id));
        $this->assertSame([], attribute::get_for_idp($idp->id));
        $this->assertSame(0, mapping::count_for_idp($idp->id));
        $this->assertCount(1, attribute::get_for_idp($otheridp->id));
        $this->assertSame(1, mapping::count_for_idp($otheridp->id));
        $this->assertTrue($DB->record_exists('user', ['id' => $user->id, 'deleted' => 0]));
    }

    public function test_get_enabled_respects_tenants(): void {
        $gen = $this->get_generator();
        $all = $gen->create_idp(['name' => 'All', 'tenantid' => null]);
        $t1 = $gen->create_idp(['name' => 'T1', 'tenantid' => 1]);
        $gen->create_idp(['name' => 'T2', 'tenantid' => 2]);
        $gen->create_idp(['name' => 'Disabled', 'enabled' => 0]);

        $this->assertEquals([$all->id], array_keys(idp::get_enabled(null)));
        $this->assertEquals([$all->id, $t1->id], array_keys(idp::get_enabled(1)));
    }

    public function test_needs_refresh(): void {
        $idp = $this->get_generator()->create_idp();
        $this->assertFalse(idp::needs_refresh($idp));

        $info = idp::get_certinfo($idp);
        $info['fetched'] = time() - idp::REFRESH_INTERVAL - 10;
        $info['attempted'] = $info['fetched'];
        $idp->certjson = json_encode($info);
        $this->assertTrue(idp::needs_refresh($idp));

        // Recent failed attempt blocks retry.
        $info['attempted'] = time() - 60;
        $idp->certjson = json_encode($info);
        $this->assertFalse(idp::needs_refresh($idp));

        // Metadata validUntil is ignored on purpose, Zitadel publishes documents valid for minutes.
        $info['attempted'] = null;
        $info['fetched'] = time();
        $info['validuntil'] = time() + 60;
        $idp->certjson = json_encode($info);
        $this->assertFalse(idp::needs_refresh($idp));

        $info['fetched'] = time() - idp::REFRESH_INTERVAL - 10;
        $idp->certjson = json_encode($info);
        $this->assertTrue(idp::needs_refresh($idp));

        $info['autorefresh'] = 0;
        $idp->certjson = json_encode($info);
        $this->assertFalse(idp::needs_refresh($idp));

        $idp->certjson = null;
        $this->assertTrue(idp::needs_refresh($idp));
    }

    public function test_autologin_warning(): void {
        $gen = $this->get_generator();

        $generic = $gen->create_idp(['provider' => 'generic', 'autologin' => 1]);
        $this->assertNull(idp::get_autologin_warning($generic), 'standard products log out centrally');

        $zitadel = $gen->create_idp(['provider' => 'zitadel', 'autologin' => 0]);
        $this->assertNull(idp::get_autologin_warning($zitadel), 'no warning without automatic login');

        $zitadel = idp::update((object)['id' => $zitadel->id, 'autologin' => 1]);
        $warning = idp::get_autologin_warning($zitadel);
        $this->assertNotNull($warning);
        $this->assertStringContainsString('Zitadel', $warning);
        $this->assertStringContainsString('shared computers', $warning);
    }

    public function test_certificates_changed(): void {
        $idp = $this->get_generator()->create_idp();
        $metadata = \auth_musaml_generator::get_fixture_metadata();
        $this->assertFalse(idp::certificates_changed($idp, $metadata));
        $metadata['certs'][] = 'QUJD';
        $this->assertTrue(idp::certificates_changed($idp, $metadata));
        $metadata['certs'] = ['QUJD'];
        $this->assertTrue(idp::certificates_changed($idp, $metadata));
    }

    public function test_refresh_metadata_failure_is_recorded_and_not_fatal(): void {
        $idp = $this->get_generator()->create_idp(['metadataurl' => 'ftp://nope']);
        $before = idp::get_certinfo($idp);

        $error = idp::refresh_metadata($idp);

        $this->assertStringContainsString('http', $error);
        $idp = idp::fetch($idp->id);
        $after = idp::get_certinfo($idp);
        $this->assertSame($error, $after['error']);
        $this->assertEqualsWithDelta(time(), $after['attempted'], 10);
        $this->assertSame($before['certs'], $after['certs'], 'old certificates are kept');
        $this->assertSame($before['fetched'], $after['fetched']);
    }

    public function test_refresh_metadata_reports_blocked_hosts(): void {
        set_config('curlsecurityblockedhosts', 'idp1.example.com');
        $idp = $this->get_generator()->create_idp();

        $error = idp::refresh_metadata($idp);

        $this->assertStringContainsString('blocked', $error);
        $this->assertStringContainsString($idp->metadataurl, $error);
    }

    public function test_idp_settings_and_build(): void {
        saml::regenerate_sp_certificate();
        $idp = $this->get_generator()->create_idp(['provider' => 'zitadel']);

        $idpsettings = idp::get_idp_settings($idp);
        $this->assertSame($idp->entityid, $idpsettings['entityId']);
        $this->assertSame($idp->ssourl, $idpsettings['singleSignOnService']['url']);
        $this->assertSame($idp->logouturl, $idpsettings['singleLogoutService']['url']);
        $this->assertCount(1, $idpsettings['x509certMulti']['signing']);
        $this->assertSame($idpsettings['x509certMulti']['signing'], $idpsettings['x509certMulti']['encryption']);

        $settings = saml::build_settings($idp);
        $this->assertSame($idpsettings, $settings['idp']);
        $this->assertTrue($settings['strict']);
        $this->assertSame([], saml::get_unsafe_overrides($settings));

        $idp = idp::update((object)['id' => $idp->id, 'customsettingsjson' => '{"strict": false, "idp": {"entityId": "x"}}']);
        $settings = saml::build_settings($idp);
        $this->assertFalse($settings['strict']);
        $this->assertSame('x', $settings['idp']['entityId']);
        $this->assertSame(['strict = false'], saml::get_unsafe_overrides($settings));

        // The library accepts the assembled settings.
        $idp = idp::update((object)['id' => $idp->id, 'customsettingsjson' => '']);
        $auth = saml::create_auth($idp);
        $this->assertSame($idp->ssourl, $auth->getSSOurl());
        $this->assertSame([], $auth->getSettings()->getErrors());
    }

    public function test_tenant_menu_without_tenancy(): void {
        $this->assertSame([], idp::get_tenant_menu());
        $idp = $this->get_generator()->create_idp();
        $this->assertSame(get_string('tenant_all', 'auth_musaml'), idp::get_tenant_name($idp));
    }
}
