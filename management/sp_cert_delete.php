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
 * Delete the service provider certificate, key and passphrase.
 *
 * Only possible while no identity provider is configured, everything would stop working
 * for a site that already uses SAML.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\saml;
use core\exception\moodle_exception;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

require_login();
$context = \core\context\system::instance();
require_capability('moodle/site:config', $context);

if (!saml::has_sp_certificate() || idp::get_all()) {
    throw new moodle_exception('error_spcertinuse', 'auth_musaml');
}

$PAGE->set_url('/auth/musaml/management/sp_cert_delete.php');
$PAGE->set_context($context);

$returnurl = new \core\url('/auth/musaml/management/sp.php');

$form = new \auth_musaml\local\form\sp_cert_delete(null, []);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($form->get_data()) {
    // The entity ID belongs to the certificate, the next one starts from the default again.
    unset_config('sp_entityid', 'auth_musaml');
    saml::delete_sp_certificate();
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
