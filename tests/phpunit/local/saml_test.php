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

use auth_musaml\local\saml;
use core\exception\moodle_exception;

/**
 * SAML helper test.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\saml
 */
final class saml_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_urls(): void {
        global $CFG;
        $this->assertSame($CFG->wwwroot . '/auth/musaml/endpoints/', saml::get_base_url());
        $this->assertSame($CFG->wwwroot . '/auth/musaml/endpoints/acs.php', saml::get_endpoint_url('acs')->out(false));
        $this->assertSame($CFG->wwwroot . '/auth/musaml/metadata.php', saml::get_sp_entityid());

        set_config('sp_entityid', 'https://example.com/sp ', 'auth_musaml');
        $this->assertSame('https://example.com/sp', saml::get_sp_entityid());
    }

    public function test_regenerate_and_delete_sp_certificate(): void {
        $this->assertFalse(saml::has_sp_certificate());
        $this->assertNull(saml::get_sp_certificate());
        $this->assertNull(saml::get_sp_certificate_info());
        $this->assertNull(saml::get_sp_private_key());

        saml::regenerate_sp_certificate();

        $this->assertTrue(saml::has_sp_certificate());
        $cert = saml::get_sp_certificate();
        $this->assertStringStartsWith('-----BEGIN CERTIFICATE-----', $cert);
        $info = saml::get_sp_certificate_info();
        $this->assertStringContainsString('CN=www.example.com', $info['subject']);
        $this->assertEqualsWithDelta(time() + 3650 * DAYSECS, $info['notafter'], 120);

        $storedkey = get_config('auth_musaml', saml::CONFIG_SP_KEY);
        $this->assertStringStartsWith('-----BEGIN ENCRYPTED PRIVATE KEY-----', $storedkey);
        $storedpass = get_config('auth_musaml', saml::CONFIG_SP_KEYPASS);
        $this->assertNotEmpty($storedpass);
        $this->assertSame(40, strlen(\core\encryption::decrypt($storedpass)));

        $plainkey = saml::get_sp_private_key();
        $this->assertStringStartsWith('-----BEGIN PRIVATE KEY-----', $plainkey);
        $this->assertTrue(openssl_x509_check_private_key($cert, $plainkey));

        // Regeneration replaces everything.
        saml::regenerate_sp_certificate(['commonName' => 'other', 'organizationName' => 'Org'], 10);
        $this->assertNotSame($cert, saml::get_sp_certificate());
        $this->assertSame('CN=other, O=Org', saml::get_sp_certificate_info()['subject']);

        saml::delete_sp_certificate();
        $this->assertFalse(saml::has_sp_certificate());
        $this->assertNull(saml::get_sp_private_key());
    }

    public function test_private_key_is_null_when_passphrase_is_broken(): void {
        saml::regenerate_sp_certificate();
        set_config(saml::CONFIG_SP_KEYPASS, \core\encryption::encrypt('wrong'), 'auth_musaml');
        $this->assertNull(saml::get_sp_private_key());
        $this->assertDebuggingCalled();
    }

    public function test_default_dn(): void {
        global $SITE;
        $dn = saml::get_default_dn();
        $this->assertSame('www.example.com', $dn['commonName']);
        $this->assertSame($SITE->shortname, $dn['organizationName']);
    }

    public function test_default_settings(): void {
        saml::regenerate_sp_certificate();
        $settings = saml::get_default_settings();

        $this->assertTrue($settings['strict']);
        $this->assertFalse($settings['debug']);
        $this->assertSame(saml::get_base_url(), $settings['baseurl']);
        $this->assertTrue($settings['security']['wantAssertionsSigned']);
        $this->assertTrue($settings['security']['rejectUnsolicitedResponsesWithInResponseTo']);
        $this->assertTrue($settings['security']['requireDestination']);
        $this->assertTrue($settings['security']['signMetadata']);
        $this->assertStringContainsString('rsa-sha256', $settings['security']['signatureAlgorithm']);
        $this->assertSame([], saml::get_unsafe_overrides($settings));

        set_config('supportemail', '');
        $this->assertSame([], saml::get_default_settings()['contactPerson']);

        set_config('supportemail', 'admin@example.com');
        set_config('supportname', '');
        $settings = saml::get_default_settings();
        $this->assertSame('admin@example.com', $settings['contactPerson']['technical']['emailAddress']);
        $this->assertSame('PHPUnit test site', $settings['contactPerson']['technical']['givenName']);
        set_config('supportname', 'Admin');
        $settings = saml::get_default_settings();
        $this->assertSame('Admin', $settings['contactPerson']['technical']['givenName']);
        $this->assertSame($settings['contactPerson']['technical'], $settings['contactPerson']['support']);

        set_config('supportemail', 'not an email');
        $this->assertSame([], saml::get_default_settings()['contactPerson']);
    }

    public function test_custom_settings_override_anything(): void {
        saml::regenerate_sp_certificate();
        $defaults = saml::get_default_settings();

        $this->assertSame($defaults, saml::apply_custom_settings($defaults, null));
        $this->assertSame($defaults, saml::apply_custom_settings($defaults, '  '));
        $this->assertSame($defaults, saml::apply_custom_settings($defaults, 'not json'));

        $json = json_encode([
            'strict' => false,
            'security' => ['wantAssertionsSigned' => false, 'requestedAuthnContext' => ['urn:x']],
            'sp' => ['NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'],
            'idp' => ['entityId' => 'x'],
        ]);
        $merged = saml::apply_custom_settings($defaults, $json);
        $this->assertFalse($merged['strict']);
        $this->assertFalse($merged['security']['wantAssertionsSigned']);
        $this->assertSame(['urn:x'], $merged['security']['requestedAuthnContext']);
        $this->assertTrue($merged['security']['wantXMLValidation'], 'untouched keys keep defaults');
        $this->assertSame('urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', $merged['sp']['NameIDFormat']);
        $this->assertSame($defaults['sp']['x509cert'], $merged['sp']['x509cert']);
        $this->assertSame('x', $merged['idp']['entityId']);
    }

    public function test_validate_custom_settings(): void {
        $this->assertNull(saml::validate_custom_settings(null));
        $this->assertNull(saml::validate_custom_settings(''));
        $this->assertNull(saml::validate_custom_settings('{}'));
        $this->assertNull(saml::validate_custom_settings('{"strict": false}'));
        $this->assertNotNull(saml::validate_custom_settings('{strict: false}'));
        $this->assertNotNull(saml::validate_custom_settings('[1, 2]'));
        $this->assertNotNull(saml::validate_custom_settings('"string"'));
    }

    public function test_unsafe_overrides_are_detected(): void {
        saml::regenerate_sp_certificate();
        $defaults = saml::get_default_settings();

        $json = json_encode([
            'strict' => false,
            'debug' => true,
            'security' => [
                'wantAssertionsSigned' => false,
                'requireDestination' => false,
                'allowRepeatAttributeName' => true,
                'signatureAlgorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                'digestAlgorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                'encryption_algorithm' => 'http://www.w3.org/2001/04/xmlenc#aes128-cbc',
                'wantMessagesSigned' => true,
            ],
            'idp' => [
                'certFingerprint' => 'aabbcc',
            ],
        ]);
        $unsafe = saml::get_unsafe_overrides(saml::apply_custom_settings($defaults, $json));
        $this->assertSame([
            'strict = false',
            'debug = true',
            'security.wantAssertionsSigned = false',
            'security.requireDestination = false',
            'security.allowRepeatAttributeName = true',
            'security.signatureAlgorithm = "http:\/\/www.w3.org\/2000\/09\/xmldsig#rsa-sha1"',
            'security.digestAlgorithm = "http:\/\/www.w3.org\/2000\/09\/xmldsig#sha1"',
            'security.encryption_algorithm = "http:\/\/www.w3.org\/2001\/04\/xmlenc#aes128-cbc"',
            'idp.certFingerprint = "aabbcc"',
        ], $unsafe);

        // Making things stricter is not flagged.
        $json = json_encode(['security' => ['wantMessagesSigned' => true, 'wantAssertionsEncrypted' => true]]);
        $this->assertSame([], saml::get_unsafe_overrides(saml::apply_custom_settings($defaults, $json)));
    }

    public function test_sp_metadata_requires_certificate(): void {
        $this->assertNull(saml::get_sp_metadata(), 'there is nothing to serve before the certificate is created');
    }

    public function test_sp_metadata_is_valid_and_signed(): void {
        global $CFG;
        set_config('supportname', 'Admin');
        set_config('supportemail', 'admin@example.com');
        saml::regenerate_sp_certificate();

        $xml = saml::get_sp_metadata();

        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $entity = $xpath->query('/md:EntityDescriptor')->item(0);
        $this->assertSame($CFG->wwwroot . '/auth/musaml/metadata.php', $entity->getAttribute('entityID'));
        $this->assertFalse($entity->hasAttribute('validUntil'));

        $spsso = $xpath->query('/md:EntityDescriptor/md:SPSSODescriptor')->item(0);
        $this->assertSame('true', $spsso->getAttribute('AuthnRequestsSigned'));
        $this->assertSame('true', $spsso->getAttribute('WantAssertionsSigned'));

        $acs = $xpath->query('//md:AssertionConsumerService')->item(0);
        $this->assertSame($CFG->wwwroot . '/auth/musaml/endpoints/acs.php', $acs->getAttribute('Location'));
        $this->assertSame('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST', $acs->getAttribute('Binding'));
        $sls = $xpath->query('//md:SingleLogoutService')->item(0);
        $this->assertSame($CFG->wwwroot . '/auth/musaml/endpoints/sls.php', $sls->getAttribute('Location'));

        $this->assertSame(1, $xpath->query('//md:KeyDescriptor[@use="signing"]')->length);
        $this->assertSame(1, $xpath->query('//md:KeyDescriptor[@use="encryption"]')->length);
        $certdata = $xpath->query('//md:KeyDescriptor[@use="signing"]//ds:X509Certificate')->item(0)->textContent;
        $this->assertSame(\auth_musaml\local\openssl::strip_pem(saml::get_sp_certificate()), trim($certdata));

        $this->assertSame(2, $xpath->query('//md:ContactPerson')->length);
        $this->assertSame('technical', $xpath->query('//md:ContactPerson')->item(0)->getAttribute('contactType'));
        $this->assertSame('admin@example.com', $xpath->query('//md:ContactPerson/md:EmailAddress')->item(0)->textContent);

        // Signature is present, uses SHA-256 and verifies against the SP certificate.
        $this->assertSame(1, $xpath->query('/md:EntityDescriptor/ds:Signature')->length);
        $this->assertStringContainsString('rsa-sha256', $xml);
        saml::init();
        $this->assertTrue(\OneLogin\Saml2\Utils::validateSign($dom, saml::get_sp_certificate()));
    }

    public function test_parse_idp_metadata(): void {
        $xml = file_get_contents(__DIR__ . '/../../fixtures/zitadel_metadata.xml');
        $metadata = saml::parse_idp_metadata($xml);

        $this->assertSame('https://zitadel.example.com/saml/v2/metadata', $metadata['entityid']);
        $this->assertSame('https://zitadel.example.com/saml/v2/SSO', $metadata['ssourl']);
        $this->assertSame('https://zitadel.example.com/saml/v2/SLO', $metadata['slourl']);
        $this->assertCount(1, $metadata['certs']);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $metadata['certs'][0]);
        $this->assertSame('urn:oasis:names:tc:SAML:2.0:nameid-format:persistent', $metadata['nameidformat']);
        $expected = ['Email', 'SurName', 'FirstName', 'FullName', 'UserName', 'UserID'];
        $this->assertSame($expected, $metadata['attributes']);
        $this->assertSame(strtotime('2026-09-20T15:38:22.167Z'), $metadata['validuntil']);
        $this->assertNull($metadata['displayname']);
    }

    public function test_parse_idp_metadata_minimal_with_display_name(): void {
        $key = \auth_musaml\local\openssl::generate_key();
        $cert = \auth_musaml\local\openssl::create_self_signed_cert($key, ['commonName' => 'idp']);
        $certdata = \auth_musaml\local\openssl::strip_pem($cert);
        $xml = <<<XML
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui" entityID="https://idp.example.com">
  <md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:Extensions><mdui:UIInfo><mdui:DisplayName xml:lang="en">Example IDP</mdui:DisplayName></mdui:UIInfo></md:Extensions>
    <md:KeyDescriptor><ds:KeyInfo><ds:X509Data>
      <ds:X509Certificate>$certdata</ds:X509Certificate>
    </ds:X509Data></ds:KeyInfo></md:KeyDescriptor>
    <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://idp.example.com/sso"/>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;
        $metadata = saml::parse_idp_metadata($xml);
        $this->assertSame('https://idp.example.com', $metadata['entityid']);
        $this->assertSame('https://idp.example.com/sso', $metadata['ssourl'], 'falls back to any binding');
        $this->assertNull($metadata['slourl']);
        $this->assertSame([$certdata], $metadata['certs']);
        $this->assertNull($metadata['nameidformat']);
        $this->assertSame([], $metadata['attributes']);
        $this->assertNull($metadata['validuntil']);
        $this->assertSame('Example IDP', $metadata['displayname']);
    }

    public function test_parse_idp_metadata_rejects_garbage(): void {
        $noidp = '<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="x"/>';
        foreach (['', 'not xml', '<root/>', $noidp] as $xml) {
            try {
                saml::parse_idp_metadata($xml);
                $this->fail('Exception expected for: ' . $xml);
            } catch (moodle_exception $e) {
                $this->assertSame('error_metadataparse', $e->errorcode);
            }
        }
    }

    public function test_parse_idp_metadata_rejects_long_entityid(): void {
        $xml = file_get_contents(__DIR__ . '/../../fixtures/zitadel_metadata.xml');
        $xml = str_replace(
            'entityID="https://zitadel.example.com/saml/v2/metadata"',
            'entityID="https://' . str_repeat('a', 250) . '.example.com/"',
            $xml
        );
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('255');
        saml::parse_idp_metadata($xml);
    }
}
