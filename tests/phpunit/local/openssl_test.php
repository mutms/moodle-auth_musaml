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

use auth_musaml\local\openssl;

/**
 * Openssl helper test.
 *
 * @group      MuTMS
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\local\openssl
 */
final class openssl_test extends \basic_testcase {
    public function test_key_and_self_signed_cert_round_trip(): void {
        $key = openssl::generate_key();
        $details = openssl_pkey_get_details($key);
        $this->assertSame(OPENSSL_KEYTYPE_RSA, $details['type']);
        $this->assertSame(2048, $details['bits']);

        $dn = ['commonName' => 'sp.example.com', 'organizationName' => 'Example'];
        $cert = openssl::create_self_signed_cert($key, $dn, 30);
        $this->assertStringStartsWith('-----BEGIN CERTIFICATE-----', $cert);
        $this->assertTrue(openssl::key_matches_cert($cert, $key));

        $info = openssl::parse_cert($cert);
        $this->assertSame('CN=sp.example.com, O=Example', $info['subject']);
        $this->assertSame($info['subject'], $info['issuer']);
        $this->assertNotSame('', $info['serial']);
        $this->assertEqualsWithDelta(time(), $info['notbefore'], 120);
        $this->assertEqualsWithDelta(time() + 30 * DAYSECS, $info['notafter'], 120);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}:){31}[0-9A-F]{2}$/', $info['fingerprint']);
        $this->assertSame($info['fingerprint'], openssl::fingerprint($cert));

        $this->assertNull(openssl::parse_cert('garbage'));
        $this->assertNull(openssl::fingerprint('garbage'));
    }

    public function test_certificate_uses_sha256_signature(): void {
        $key = openssl::generate_key();
        $cert = openssl::create_self_signed_cert($key, ['commonName' => 'x']);
        $parsed = openssl_x509_parse($cert);
        $this->assertSame('RSA-SHA256', $parsed['signatureTypeSN']);
    }

    public function test_encrypted_key_export_and_load(): void {
        $key = openssl::generate_key();
        $pem = openssl::export_key($key, 'secret');
        $this->assertStringStartsWith('-----BEGIN ENCRYPTED PRIVATE KEY-----', $pem);

        $this->assertNull(openssl::load_key($pem, 'wrong'));
        $loaded = openssl::load_key($pem, 'secret');
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $loaded);

        $plain = openssl::export_key_plain($loaded);
        $this->assertStringStartsWith('-----BEGIN PRIVATE KEY-----', $plain);
        $this->assertSame(openssl_pkey_get_details($key)['key'], openssl_pkey_get_details($loaded)['key']);
    }

    public function test_export_key_requires_passphrase(): void {
        $key = openssl::generate_key();
        $this->expectException(\InvalidArgumentException::class);
        openssl::export_key($key, '');
    }

    public function test_strip_pem(): void {
        $key = openssl::generate_key();
        $cert = openssl::create_self_signed_cert($key, ['commonName' => 'x']);
        $stripped = openssl::strip_pem($cert);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $stripped);
        $this->assertSame(openssl::strip_pem($cert), openssl::strip_pem("  $cert\n\n"));
        $rebuilt = "-----BEGIN CERTIFICATE-----\n" . chunk_split($stripped, 64, "\n") . "-----END CERTIFICATE-----\n";
        $this->assertNotFalse(openssl_x509_read($rebuilt));
    }
}
