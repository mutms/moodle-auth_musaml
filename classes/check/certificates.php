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

namespace auth_musaml\check;

use auth_musaml\local\idp;
use auth_musaml\local\saml;
use core\check\check;
use core\check\result;
use core\url;

/**
 * Warns before service provider or identity provider certificates expire.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certificates extends check {
    /** @var int certificates expiring within this time are an error */
    public const SOON = WEEKSECS;

    /** @var int certificates expiring within this time are a warning */
    public const APPROACHING = 4 * WEEKSECS;

    #[\Override]
    public function get_id(): string {
        return 'auth_musaml_certificates';
    }

    #[\Override]
    public function get_name(): string {
        return get_string('check_certificates', 'auth_musaml');
    }

    #[\Override]
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new url('/auth/musaml/management/idps.php'),
            get_string('idps', 'auth_musaml')
        );
    }

    #[\Override]
    public function get_result(): result {
        if (!is_enabled_auth('musaml')) {
            return new result(result::NA, get_string('check_certificates_disabled', 'auth_musaml'), '');
        }
        if (!saml::has_sp_certificate()) {
            return new result(result::WARNING, get_string('sp_cert_missing', 'auth_musaml'), '');
        }

        $now = time();
        $status = result::OK;
        $details = [];
        $worst = null;

        foreach ($this->get_certificates() as $entry) {
            [$label, $notafter] = $entry;
            if (!$notafter) {
                continue;
            }
            $delta = format_time($notafter - $now);
            if ($notafter <= $now) {
                $entrystatus = result::CRITICAL;
                $summary = get_string('check_certificates_expired', 'auth_musaml', (object)$this->info($label, $delta));
            } else if ($notafter <= $now + self::SOON) {
                $entrystatus = result::ERROR;
                $summary = get_string('check_certificates_soon', 'auth_musaml', (object)$this->info($label, $delta));
            } else if ($notafter <= $now + self::APPROACHING) {
                $entrystatus = result::WARNING;
                $summary = get_string('check_certificates_soon', 'auth_musaml', (object)$this->info($label, $delta));
            } else {
                $entrystatus = result::OK;
                $summary = get_string('check_certificates_ok', 'auth_musaml', (object)$this->info($label, $delta));
            }
            $details[] = $summary;
            if ($this->is_worse($entrystatus, $status)) {
                $status = $entrystatus;
                $worst = $summary;
            }
        }

        if (!$details) {
            return new result(result::WARNING, get_string('check_certificates_none', 'auth_musaml'), '');
        }

        $summary = $worst ?? get_string('check_certificates_allok', 'auth_musaml', count($details));
        return new result($status, $summary, \core\output\html_writer::alist($details));
    }

    /**
     * Certificates to check.
     *
     * @return array list of [label, notafter]
     */
    private function get_certificates(): array {
        $result = [];

        $info = saml::get_sp_certificate_info();
        if ($info) {
            $result[] = [get_string('sp', 'auth_musaml'), $info['notafter']];
        }
        foreach (idp::get_all() as $idp) {
            if (!$idp->enabled) {
                continue;
            }
            foreach (idp::get_certinfo($idp)['certs'] as $cert) {
                $result[] = [format_string($idp->name), $cert['notafter']];
            }
        }
        return $result;
    }

    /**
     * Message placeholders.
     *
     * @param string $label
     * @param string $delta
     * @return array
     */
    private function info(string $label, string $delta): array {
        return ['name' => $label, 'time' => $delta];
    }

    /**
     * Is the first status more serious than the second?
     *
     * @param string $status
     * @param string $current
     * @return bool
     */
    private function is_worse(string $status, string $current): bool {
        $order = [result::OK => 0, result::INFO => 1, result::WARNING => 2, result::ERROR => 3, result::CRITICAL => 4];
        return ($order[$status] ?? 0) > ($order[$current] ?? 0);
    }
}
