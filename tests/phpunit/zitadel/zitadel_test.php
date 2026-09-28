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

namespace auth_musaml\phpunit\zitadel;

use auth_musaml\local\idp;
use auth_musaml\local\provider\zitadel;
use auth_musaml\local\saml;
use auth_musaml\tests\zitadel_client;

/**
 * Tests against the Zitadel test instance, skipped unless TEST_AUTH_MUSAML_ZITADEL_* constants are set.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\idp
 */
final class zitadel_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        if (!zitadel_client::is_available()) {
            $this->markTestSkipped('TEST_AUTH_MUSAML_ZITADEL_* constants are not defined');
        }
        $this->resetAfterTest();
        set_config('curlsecurityblockedhosts', '');
    }

    public function test_metadata_download_detects_zitadel(): void {
        $url = zitadel_client::get_metadata_url();
        $metadata = idp::load_metadata($url);

        $this->assertStringStartsWith(TEST_AUTH_MUSAML_ZITADEL_URL, $metadata['entityid']);
        $this->assertNotEmpty($metadata['certs']);
        $this->assertContains('UserID', $metadata['attributes']);
        $this->assertSame(zitadel::class, \auth_musaml\local\provider\base::detect_class($metadata));

        $idp = idp::create((object)['metadataurl' => $url, 'name' => 'Zitadel', 'mapattr' => 'UserID', 'metadata' => $metadata]);
        $this->assertNull(idp::refresh_metadata($idp));
        $this->assertNull(idp::get_certinfo(idp::fetch($idp->id))['error']);
    }

    public function test_sp_registration(): void {
        saml::regenerate_sp_certificate();
        $projectid = zitadel_client::ensure_saml_app();
        $this->assertNotEmpty($projectid);

        $apps = zitadel_client::call('POST', "/management/v1/projects/$projectid/apps/_search", []);
        $this->assertCount(1, $apps['result']);
        $this->assertSame('moodle', $apps['result'][0]['name']);

        // Second call replaces the application instead of adding another one.
        $this->assertSame($projectid, zitadel_client::ensure_saml_app());
        $apps = zitadel_client::call('POST', "/management/v1/projects/$projectid/apps/_search", []);
        $this->assertCount(1, $apps['result']);
    }
}
