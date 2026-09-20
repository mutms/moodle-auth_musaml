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

namespace auth_musaml\phpunit\local\provider;

use auth_musaml\local\provider\adfs;
use auth_musaml\local\provider\base;
use auth_musaml\local\provider\entra;
use auth_musaml\local\provider\generic;
use auth_musaml\local\provider\google;
use auth_musaml\local\provider\keycloak;
use auth_musaml\local\provider\okta;
use auth_musaml\local\provider\zitadel;
use auth_musaml\local\saml;

/**
 * Provider classes test.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\provider\base
 * @covers \auth_musaml\local\provider\generic
 * @covers \auth_musaml\local\provider\zitadel
 */
final class provider_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        // Loads the generator class for the static fixture helper.
        $this->getDataGenerator()->get_plugin_generator('auth_musaml');
    }

    public function test_registry(): void {
        $all = base::get_all();
        $this->assertSame('generic', array_key_first($all));
        $this->assertSame(generic::class, $all['generic']);
        $this->assertSame(zitadel::class, $all['zitadel']);
        $this->assertArrayNotHasKey('base', $all);

        $menu = base::get_menu();
        $this->assertSame(array_keys($all), array_keys($menu));
        $this->assertSame('Zitadel', $menu['zitadel']);

        $this->assertSame('generic', generic::get_type());
        $this->assertSame('zitadel', zitadel::get_type());
        $this->assertSame(zitadel::class, base::get_class('zitadel'));
        $this->assertSame(generic::class, base::get_class('unknown'));
        $this->assertSame(generic::class, base::get_class(null));
    }

    public function test_detection(): void {
        $metadata = \auth_musaml_generator::get_fixture_metadata();
        $this->assertSame(zitadel::class, base::detect_class($metadata));
        $this->assertTrue(zitadel::detect($metadata));
        $this->assertTrue(generic::detect($metadata));

        $metadata['entityid'] = 'https://idp.example.com/simplesaml/saml2/idp/metadata.php';
        $this->assertTrue(zitadel::detect($metadata), 'attribute names still identify Zitadel');
        $metadata['attributes'] = ['mail', 'uid'];
        $this->assertFalse(zitadel::detect($metadata));
        $this->assertSame(generic::class, base::detect_class($metadata));
    }

    public function test_form_defaults(): void {
        $metadata = \auth_musaml_generator::get_fixture_metadata();

        $defaults = generic::get_form_defaults($metadata);
        $this->assertSame('zitadel.example.com', $defaults['name']);
        $this->assertSame(base::NAMEID_ATTRIBUTE, $defaults['mapattr']);
        $this->assertSame(0, $defaults['attrsimple']);
        $this->assertSame([], $defaults['attributes']);

        $defaults = zitadel::get_form_defaults($metadata);
        $this->assertSame('Zitadel', $defaults['name']);
        $this->assertSame('UserID', $defaults['mapattr']);
        $this->assertSame(['UserName', 'Email', 'FirstName', 'SurName'], array_column($defaults['attributes'], 0));
        $this->assertSame(['username', 'email', 'firstname', 'lastname'], array_column($defaults['attributes'], 1));
    }

    /**
     * Metadata as the named product would publish it.
     *
     * @param string $entityid
     * @param string $ssourl
     * @return array
     */
    private function get_metadata(string $entityid, string $ssourl = ''): array {
        return [
            'entityid' => $entityid,
            'ssourl' => $ssourl ?: ($entityid . '/sso'),
            'slourl' => null,
            'certs' => ['QUJD'],
            'nameidformat' => null,
            'attributes' => [],
            'validuntil' => null,
            'displayname' => null,
        ];
    }

    /**
     * Metadata samples that must resolve to each product.
     *
     * @return array
     */
    public static function detection_provider(): array {
        return [
            'entra tenant' => [entra::class, 'https://sts.windows.net/1f3e-42/', ''],
            'entra login host' => [entra::class, 'urn:example', 'https://login.microsoftonline.com/1f3e/saml2'],
            'adfs trust' => [adfs::class, 'http://adfs.example.com/adfs/services/trust', ''],
            'adfs endpoint' => [adfs::class, 'urn:example', 'https://adfs.example.com/adfs/ls/'],
            'keycloak realm' => [keycloak::class, 'https://kc.example.com/realms/master', ''],
            'keycloak endpoint' => [keycloak::class, 'urn:example', 'https://kc.example.com/realms/x/protocol/saml'],
            'okta entity' => [okta::class, 'http://www.okta.com/exk1fa', ''],
            'okta host' => [okta::class, 'urn:example', 'https://example.okta.com/app/example/exk1fa/sso/saml'],
            'google' => [google::class, 'https://accounts.google.com/o/saml2?idpid=C01', ''],
            'unknown product' => [generic::class, 'https://idp.example.com/metadata', ''],
        ];
    }

    /**
     * Real metadata of each product, anonymised, so detection is tested against the
     * documents these products actually publish and not against invented values.
     *
     * @return array
     */
    public static function fixture_provider(): array {
        return [
            'zitadel' => ['zitadel_metadata.xml', zitadel::class, 'https://zitadel.example.com/saml/v2/metadata', true],
            'keycloak' => ['keycloak_metadata.xml', keycloak::class, 'https://keycloak.example.com/realms/master', true],
            'okta' => ['okta_metadata.xml', okta::class, 'http://www.okta.com/exkEXAMPLE0000000000', true],
            'google' => ['google_metadata.xml', google::class, 'https://accounts.google.com/o/saml2?idpid=C00example', false],
        ];
    }

    /**
     * @dataProvider fixture_provider
     *
     * @param string $fixture
     * @param string $expected provider class
     * @param string $entityid
     * @param bool $haslogout
     */
    public function test_real_metadata_of_known_products(
        string $fixture,
        string $expected,
        string $entityid,
        bool $haslogout
    ): void {
        $xml = file_get_contents(__DIR__ . '/../../../fixtures/' . $fixture);
        $metadata = saml::parse_idp_metadata($xml);

        $this->assertSame($entityid, $metadata['entityid']);
        $this->assertNotEmpty($metadata['ssourl']);
        $this->assertNotEmpty($metadata['certs']);
        $this->assertSame($expected, base::detect_class($metadata));

        // Products without a logout service must not advertise single logout.
        $this->assertSame($haslogout, $metadata['slourl'] !== null, 'single logout service');
        if (!$haslogout) {
            $this->assertFalse($expected::supports_slo());
        }

        // The defaults a real document produces are the ones administrators see.
        $defaults = $expected::get_form_defaults($metadata);
        $this->assertNotEmpty($defaults['mapattr']);
        $this->assertNotEmpty($defaults['attributes']);
    }

    /**
     * @dataProvider detection_provider
     * @param string $expected
     * @param string $entityid
     * @param string $ssourl
     */
    public function test_detection_of_known_products(string $expected, string $entityid, string $ssourl): void {
        $metadata = $this->get_metadata($entityid, $ssourl);
        $this->assertSame($expected, base::detect_class($metadata));
    }

    public function test_known_products_are_registered(): void {
        $menu = base::get_menu();
        foreach (['generic', 'zitadel', 'entra', 'adfs', 'keycloak', 'okta', 'google'] as $type) {
            $this->assertArrayHasKey($type, $menu);
            $this->assertNotEmpty($menu[$type]);
        }
    }

    public function test_form_defaults_of_known_products(): void {
        $required = ['username', 'email', 'firstname', 'lastname'];

        foreach (base::get_all() as $type => $class) {
            $defaults = $class::get_form_defaults($this->get_metadata('https://idp.example.com'));
            $this->assertNotEmpty($defaults['name'], $type);
            $this->assertNotEmpty($defaults['mapattr'], $type);
            foreach ($defaults['attributes'] as $row) {
                $this->assertCount(4, $row, $type);
                $this->assertNotEmpty($row[0], $type);
                $this->assertContains($row[1], \auth_musaml\local\userfield::get_core_fields(), $type);
            }
            if ($type === 'generic') {
                continue;
            }
            // Every known product fills at least the fields an account needs.
            $fields = array_column($defaults['attributes'], 1);
            $missing = array_diff($required, $fields);
            $this->assertSame([], array_values($missing), $type);
        }
    }

    public function test_product_settings_tweaks(): void {
        $idp = (object)['id' => 1, 'provider' => 'adfs'];
        $settings = ['sp' => ['NameIDFormat' => 'urn:unspecified'], 'security' => ['lowercaseUrlencoding' => false]];

        $adfs = adfs::adjust_settings($settings, $idp);
        $this->assertTrue($adfs['security']['lowercaseUrlencoding'], 'ADFS signs lower case encoded parameters');

        $google = google::adjust_settings($settings, $idp);
        $this->assertSame(\OneLogin\Saml2\Constants::NAMEID_EMAIL_ADDRESS, $google['sp']['NameIDFormat']);

        $keycloak = keycloak::adjust_settings($settings, $idp);
        $this->assertSame(
            \OneLogin\Saml2\Constants::NAMEID_PERSISTENT,
            $keycloak['sp']['NameIDFormat'],
            'Keycloak sends the username as NameID unless a format is requested'
        );
        $this->assertTrue(
            $keycloak['security']['allowRepeatAttributeName'],
            'the role_list scope of every realm repeats the Role attribute'
        );

        $this->assertSame($settings, entra::adjust_settings($settings, $idp));
        $this->assertSame($settings, okta::adjust_settings($settings, $idp));
    }

    public function test_slo_support(): void {
        $this->assertTrue(generic::supports_slo());
        $this->assertTrue(entra::supports_slo());
        $this->assertTrue(adfs::supports_slo());
        $this->assertTrue(keycloak::supports_slo());
        $this->assertTrue(okta::supports_slo());
        $this->assertFalse(zitadel::supports_slo(), 'Zitadel refuses logout requests');
        $this->assertFalse(google::supports_slo(), 'Google Workspace has no logout service');
    }

    public function test_noop_hooks(): void {
        $idp = (object)['id' => 1, 'provider' => 'generic'];
        $settings = ['strict' => true, 'security' => ['x' => 1]];
        $this->assertSame($settings, generic::adjust_settings($settings, $idp));
        $this->assertSame($settings, zitadel::adjust_settings($settings, $idp));
        $attributes = ['nameid' => ['abc'], 'Email' => ['a@example.com']];
        $this->assertSame($attributes, generic::filter_attributes($attributes, $idp));
        $this->assertSame($attributes, zitadel::filter_attributes($attributes, $idp));
    }
}
