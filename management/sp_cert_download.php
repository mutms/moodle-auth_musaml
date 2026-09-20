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

/**
 * Service provider certificate as a file.
 *
 * Identity providers configured by hand ask for the certificate on its own, Okta wants
 * it to verify our logout requests. It is public, the same value sits in the metadata.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\saml;

require(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', \core\context\system::instance());

$certificate = saml::get_sp_certificate();
if ($certificate === null) {
    redirect(new \core\url('/auth/musaml/management/sp.php'),
        get_string('sp_cert_missing', 'auth_musaml'), null, \core\output\notification::NOTIFY_WARNING);
}

$host = parse_url($CFG->wwwroot, PHP_URL_HOST) ?: 'moodle';
$filename = clean_filename($host . '-musaml.crt');

header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: application/x-pem-file');
// The length is in bytes, strlen() must not be replaced with a multibyte function.
header('Content-Length: ' . strlen($certificate));
echo $certificate;
