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
 * Create a new service provider certificate and private key with a passphrase.
 *
 * SP cert and key are stored in config_plugins, the passphrase is encrypted using
 * standard Moodle secret stuff and stored in config_plugins too.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\form\sp_cert_regen;
use auth_musaml\local\saml;
use tool_mulib\muform\handler;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var core_renderer $OUTPUT */
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

require_login();

$context = \core\context\system::instance();
require_capability('moodle/site:config', $context);

$pageurl = new \core\url('/auth/musaml/management/sp_cert_regen.php', []);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);

$handler = handler::from_request();

$returnurl = new \core\url('/auth/musaml/management/sp.php');

$info = saml::get_sp_certificate_info();
$form = new sp_cert_regen($pageurl, [], ['info' => $info]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}
if ($data = $form->get_data()) {
    $dn = saml::get_default_dn();
    $dn['commonName'] = $data->commonname;
    $dn['organizationName'] = $data->organizationname;
    // The entity ID changes only together with the certificate, both force a re-registration.
    // It is always stored, so it cannot follow a later wwwroot change.
    set_config('sp_entityid', trim((string)$data->entityid), 'auth_musaml');
    saml::regenerate_sp_certificate($dn, $data->days);
    $handler->submitted($returnurl);
}

$title = get_string($info ? 'sp_cert_regen' : 'sp_cert_create', 'auth_musaml');
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$handler->render($form);
