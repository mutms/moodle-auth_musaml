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
 * Service provider certificate and metadata.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\saml;
use auth_musaml\output\sp_urls;
use core\output\html_writer;
use tool_mulib\output\muform\dialog\button;
use tool_mulib\output\entity_details;
use tool_mulib\output\header_actions;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var core_renderer $OUTPUT */
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', \core\context\system::instance());

admin_externalpage_setup('auth_musaml_sp', '', null, '', ['nosearch' => true]);

$PAGE->set_heading(get_string('sp', 'auth_musaml'));

$regenurl = new \core\url('/auth/musaml/management/sp_cert_regen.php');
$certinfo = saml::get_sp_certificate_info();
$idps = idp::get_all();

// With a certificate the actions belong in the header, without one there is only the big button below.
if ($certinfo) {
    $actions = new header_actions(get_string('actions'));
    $button = new button($regenurl, get_string('sp_cert_regen', 'auth_musaml'));
    $button->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_RELOAD);
    $actions->add_button($button);

    // Identity providers configured by hand ask for the certificate as a file.
    $downloadurl = new \core\url('/auth/musaml/management/sp_cert_download.php');
    $actions->get_dropdown()->add_item(get_string('sp_cert_download', 'auth_musaml'), $downloadurl);

    // Deleting is safe only while nothing depends on the certificate.
    if (!$idps) {
        $deleteurl = new \core\url('/auth/musaml/management/sp_cert_delete.php');
        $button = new button($deleteurl, get_string('sp_cert_delete', 'auth_musaml'));
        $button->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_RELOAD);
        $actions->add_button($button);
    }

    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

if (!saml::is_site_secure()) {
    // Logins from an identity provider on another site cannot work without https.
    echo $OUTPUT->notification(get_string('error_nohttps', 'auth_musaml'), 'warning', false);
}

// Nothing is registered anywhere before the certificate exists, the entity ID is not settled either.
if (!$certinfo) {
    echo $OUTPUT->notification(get_string('sp_cert_missing', 'auth_musaml'), 'warning', false);

    $button = new button($regenurl, get_string('sp_cert_create', 'auth_musaml'), true);
    $button->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_RELOAD);
    echo html_writer::div($OUTPUT->render($button), 'mb-3');
} else {
    $details = new entity_details();
    sp_urls::add_to_details($details, $OUTPUT);
    $details->add(get_string('sp_cert_subject', 'auth_musaml'), s($certinfo['subject']));
    $details->add(get_string('sp_cert_notafter', 'auth_musaml'), userdate($certinfo['notafter']));
    $details->add(get_string('sp_cert_fingerprint', 'auth_musaml'), s($certinfo['fingerprint']));
    echo $OUTPUT->render($details);

    // Every certificate change ends on the identity provider page, either to add one or to
    // hand the new metadata to those that exist.
    $idpsurl = new \core\url('/auth/musaml/management/idps.php');
    $class = $idps ? 'btn btn-secondary' : 'btn btn-primary';
    echo html_writer::div(html_writer::link($idpsurl, get_string('idps', 'auth_musaml'), ['class' => $class]), 'mb-3');
}

echo $OUTPUT->footer();
