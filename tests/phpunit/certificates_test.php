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

namespace auth_musaml\phpunit;

use auth_musaml\check\certificates;
use auth_musaml\local\idp;
use auth_musaml\local\openssl;
use auth_musaml\local\saml;
use core\check\result;

/**
 * Certificate check and certificate management test.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \auth_musaml\check\certificates
 * @covers \auth_musaml\local\idp
 */
final class certificates_test extends \advanced_testcase {
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
     * Set the expiry of the stored identity provider certificate.
     *
     * @param \stdClass $idp
     * @param int $notafter
     */
    private function set_idp_expiry(\stdClass $idp, int $notafter): void {
        global $DB;
        $info = idp::get_certinfo($idp);
        $info['certs'][0]['notafter'] = $notafter;
        $DB->set_field('auth_musaml_idp', 'certjson', json_encode($info), ['id' => $idp->id]);
    }

    public function test_check_reports_the_worst_certificate(): void {
        set_config('auth', 'musaml');
        $check = new certificates();

        $this->assertSame(result::WARNING, $check->get_result()->get_status(), 'no service provider certificate yet');

        saml::regenerate_sp_certificate();
        $idp = $this->get_generator()->create_idp();
        $this->set_idp_expiry($idp, time() + YEARSECS);
        $this->assertSame(result::OK, $check->get_result()->get_status());

        $this->set_idp_expiry($idp, time() + 2 * WEEKSECS);
        $result = $check->get_result();
        $this->assertSame(result::WARNING, $result->get_status());
        $this->assertStringContainsString('expires in', $result->get_summary());

        $this->set_idp_expiry($idp, time() + 2 * DAYSECS);
        $this->assertSame(result::ERROR, $check->get_result()->get_status());

        $this->set_idp_expiry($idp, time() - DAYSECS);
        $result = $check->get_result();
        $this->assertSame(result::CRITICAL, $result->get_status());
        $this->assertStringContainsString('expired', $result->get_summary());

        // Disabled identity providers are ignored, disabled plugin means nothing to check.
        idp::update((object)['id' => $idp->id, 'enabled' => 0]);
        $this->assertSame(result::OK, $check->get_result()->get_status());

        set_config('auth', '');
        $this->assertSame(result::NA, $check->get_result()->get_status());
    }

    public function test_check_is_registered(): void {
        $checks = \core\check\manager::get_status_checks();
        $ids = array_map(fn($check) => $check->get_id(), $checks);
        $this->assertContains('auth_musaml_certificates', $ids);
    }

    public function test_split_and_wrap_certificates(): void {
        $key = openssl::generate_key();
        $first = openssl::create_self_signed_cert($key, ['commonName' => 'one']);
        $second = openssl::create_self_signed_cert($key, ['commonName' => 'two']);

        $certs = idp::split_certificates($first . "\n" . $second);
        $this->assertCount(2, $certs);
        $this->assertSame(openssl::strip_pem($first), $certs[0]);
        $this->assertSame(openssl::strip_pem($second), $certs[1]);
        $this->assertSame($first, idp::to_pem($certs[0]));

        // Bare base64 without armour is accepted too.
        $this->assertSame([openssl::strip_pem($first)], idp::split_certificates(openssl::strip_pem($first)));
        $this->assertSame([], idp::split_certificates("  \n "));
    }

    public function test_extra_certificates_are_trusted_and_listed(): void {
        saml::regenerate_sp_certificate();
        $idp = $this->get_generator()->create_idp();
        $metadatacert = idp::get_certinfo($idp)['certs'][0]['cert'];

        $key = openssl::generate_key();
        $rollover = openssl::create_self_signed_cert($key, ['commonName' => 'rollover']);

        $idp = idp::update_certinfo($idp, false, $rollover);
        $info = idp::get_certinfo($idp);

        $this->assertSame(0, $info['autorefresh']);
        $this->assertCount(1, $info['extracerts']);
        $this->assertSame('CN=rollover', $info['extracerts'][0]['subject']);
        $this->assertNotEmpty($info['extracerts'][0]['fingerprint']);
        $this->assertFalse(idp::needs_refresh($idp), 'automatic refresh is off');

        $settings = idp::get_idp_settings($idp);
        $this->assertSame([$metadatacert, openssl::strip_pem($rollover)], $settings['x509certMulti']['signing']);
        $this->assertSame($settings['x509certMulti']['signing'], $settings['x509certMulti']['encryption']);

        // The library accepts the result.
        $auth = saml::create_auth($idp);
        $this->assertSame([], $auth->getSettings()->getErrors());

        // Clearing the field removes them again.
        $idp = idp::update_certinfo($idp, true, '');
        $this->assertSame([], idp::get_certinfo($idp)['extracerts']);
        $this->assertSame(1, idp::get_certinfo($idp)['autorefresh']);
    }

    public function test_extra_certificates_reject_garbage(): void {
        $idp = $this->get_generator()->create_idp();
        $this->expectException(\core\exception\moodle_exception::class);
        idp::update_certinfo($idp, true, 'this is not a certificate');
    }

    public function test_metadata_refresh_keeps_manual_certificates(): void {
        $idp = $this->get_generator()->create_idp();
        $key = openssl::generate_key();
        $rollover = openssl::create_self_signed_cert($key, ['commonName' => 'rollover']);
        $idp = idp::update_certinfo($idp, true, $rollover);

        $metadata = \auth_musaml_generator::get_fixture_metadata();
        $metadata['entityid'] = $idp->entityid;
        $idp = idp::apply_metadata($idp, $metadata);

        $info = idp::get_certinfo($idp);
        $this->assertCount(1, $info['certs']);
        $this->assertCount(1, $info['extracerts'], 'manual certificates survive a refresh');
    }
}
