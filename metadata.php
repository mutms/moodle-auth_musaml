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
 * SP Metadata endpoint.
 *
 * Public, the IDP downloads this URL when the service provider is registered.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\saml;

// phpcs:disable moodle.Files.RequireLogin.Missing
define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');

$download = optional_param('download', 0, PARAM_BOOL);

// Served even when the auth plugin is disabled, IDPs are usually registered before enabling logins.
// There is nothing to serve until the SP certificate is created.
$metadata = saml::get_sp_metadata();
if ($metadata === null) {
    header('HTTP/1.1 404 Not Found');
    die;
}

if ($download) {
    header('Content-Disposition: attachment; filename="sp-metadata.xml"');
    header('Content-Type: application/samlmetadata+xml; charset=utf-8');
} else {
    header('Content-Type: text/xml; charset=utf-8');
}
// The length is in bytes, strlen() must not be replaced with a multibyte function.
header('Content-Length: ' . strlen($metadata));
echo $metadata;
