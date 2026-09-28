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

namespace auth_musaml\phpunit\keycloak;

use auth_musaml\local\idp;
use auth_musaml\local\provider\keycloak;
use auth_musaml\local\saml;
use auth_musaml\tests\keycloak_client;

/**
 * Tests against a real Keycloak instance.
 *
 * Skipped unless the TEST_AUTH_MUSAML_KEYCLOAK_* constants are defined, see
 * docs/providers/keycloak.md.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\provider\keycloak
 */
final class keycloak_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        if (!keycloak_client::is_available()) {
            $this->markTestSkipped('TEST_AUTH_MUSAML_KEYCLOAK_* constants are not defined');
        }
        $this->resetAfterTest();
        set_config('curlsecurityblockedhosts', '');
    }

    public function test_metadata_download_detects_keycloak(): void {
        saml::regenerate_sp_certificate();
        keycloak_client::ensure_saml_app();

        $url = keycloak_client::get_metadata_url();
        $metadata = idp::load_metadata($url);

        $this->assertStringStartsWith(TEST_AUTH_MUSAML_KEYCLOAK_URL, $metadata['entityid']);
        $this->assertStringEndsWith('/realms/' . keycloak_client::get_realm(), $metadata['entityid']);
        $this->assertNotEmpty($metadata['certs']);
        $this->assertSame(keycloak::class, \auth_musaml\local\provider\base::detect_class($metadata));

        // Keycloak advertises no attributes, the names come from the client mappers.
        $this->assertSame([], $metadata['attributes']);
    }

    public function test_idp_creation_and_metadata_refresh(): void {
        saml::regenerate_sp_certificate();
        keycloak_client::ensure_saml_app();

        $url = keycloak_client::get_metadata_url();
        $metadata = idp::load_metadata($url);
        $defaults = keycloak::get_form_defaults($metadata);

        $idp = idp::create((object)[
            'provider' => 'keycloak',
            'metadataurl' => $url,
            'name' => 'Keycloak',
            'mapattr' => $defaults['mapattr'],
            'metadata' => $metadata,
            'attributes' => $defaults['attributes'],
        ]);

        $this->assertSame('keycloak', $idp->provider);
        $this->assertNull(idp::refresh_metadata($idp));
        $this->assertNull(idp::get_certinfo(idp::fetch($idp->id))['error']);

        // The NameID carries the user id, Keycloak has no permanent id attribute.
        $this->assertSame('nameid', $idp->mapattr);
    }

    public function test_settings_answer_the_product_quirks(): void {
        saml::regenerate_sp_certificate();
        keycloak_client::ensure_saml_app();

        $url = keycloak_client::get_metadata_url();
        $metadata = idp::load_metadata($url);
        $idp = idp::create((object)[
            'provider' => 'keycloak',
            'metadataurl' => $url,
            'name' => 'Keycloak',
            'mapattr' => 'nameid',
            'metadata' => $metadata,
        ]);

        $settings = saml::build_settings($idp);

        // A username can be renamed and reused, the persistent id cannot.
        $this->assertSame(
            \OneLogin\Saml2\Constants::NAMEID_PERSISTENT,
            $settings['sp']['NameIDFormat'],
            'Keycloak sends the username as NameID unless a format is requested'
        );

        // Every realm ships the role_list scope, which repeats the Role attribute.
        $this->assertTrue(
            $settings['security']['allowRepeatAttributeName'],
            'a realm with roles would otherwise be rejected'
        );

        // Nothing else was loosened.
        $this->assertTrue($settings['strict']);
        $this->assertTrue($settings['security']['wantAssertionsSigned']);
        $this->assertTrue($settings['security']['rejectUnsolicitedResponsesWithInResponseTo']);
        $this->assertSame(
            ['security.allowRepeatAttributeName = true'],
            saml::get_unsafe_overrides($settings),
            'the repeated name is the only relaxed setting and it is reported'
        );
    }

    public function test_single_logout_is_supported(): void {
        $this->assertTrue(keycloak::supports_slo(), 'unlike Zitadel, Keycloak accepts logout requests');
    }
}
