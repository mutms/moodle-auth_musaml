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
 * List of all IDPs.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\provider\base as provider;
use auth_musaml\local\saml;
use auth_musaml\output\sp_urls;
use core\output\html_writer;
use tool_mulib\output\muform\dialog\button;
use tool_mulib\output\entity_details;
use tool_mulib\output\header_actions;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', \core\context\system::instance());

admin_externalpage_setup('auth_musaml_idps', '', null, '', ['nosearch' => true]);

$PAGE->set_heading(get_string('idps', 'auth_musaml'));

$actions = new header_actions(get_string('actions'));

if (saml::has_sp_certificate()) {
    $url = new \core\url('/auth/musaml/management/idp_create.php');
    $button = new button($url, get_string('idp_create', 'auth_musaml'), true);
    $button->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_REDIRECT);
    $button->set_form_size('xl');
    $actions->add_button($button);
}

$PAGE->add_header_action($OUTPUT->render($actions));

echo $OUTPUT->header();

if (!saml::is_site_secure()) {
    // Logins from an identity provider on another site cannot work without https.
    echo $OUTPUT->notification(get_string('error_nohttps', 'auth_musaml'), 'warning', false);
}

$hascert = saml::has_sp_certificate();

if (!$hascert) {
    // Nothing can be added before the service provider has a certificate, send them there.
    echo $OUTPUT->notification(get_string('sp_cert_missing', 'auth_musaml'), 'warning', false);
    $spurl = new \core\url('/auth/musaml/management/sp.php');
    $link = html_writer::link($spurl, get_string('sp', 'auth_musaml'), ['class' => 'btn btn-primary']);
    echo html_writer::div($link, 'mb-3');
} else {
    // These are the values an identity provider asks for, keep them on the page where
    // identity providers are set up.
    $details = new entity_details();
    sp_urls::add_to_details($details, $OUTPUT);
    echo $OUTPUT->render($details);
}

$idps = idp::get_all();
if (!$idps) {
    // The steps only make sense once the certificate exists.
    if ($hascert) {
        $info = html_writer::tag('p', get_string('idps_none', 'auth_musaml') . ' ' . get_string('idps_none_info', 'auth_musaml'));
        $info .= html_writer::tag('p', get_string('idps_none_info2', 'auth_musaml'), ['class' => 'mb-0']);
        echo $OUTPUT->notification($info, 'info', false);
    }
} else {
    $tenancy = \tool_mulib\local\mulib::is_mutenancy_active();
    $table = new html_table();
    $table->head = [
        get_string('idp_name', 'auth_musaml'),
        get_string('idp_provider', 'auth_musaml'),
        get_string('idp_entityid', 'auth_musaml'),
        get_string('idp_enabled', 'auth_musaml'),
    ];
    if ($tenancy) {
        $table->head[] = get_string('idp_tenant', 'auth_musaml');
    }
    $table->head[] = get_string('idp_certificates', 'auth_musaml');
    foreach ($idps as $idp) {
        $providerclass = provider::get_class($idp->provider);
        $certinfo = idp::get_certinfo($idp);
        $expires = [];
        foreach ($certinfo['certs'] as $cert) {
            if (!$cert['notafter']) {
                continue;
            }
            $date = userdate($cert['notafter'], get_string('strftimedate', 'langconfig'));
            $expires[] = $cert['notafter'] < time() ? \core\output\html_writer::span($date, 'text-danger') : $date;
        }
        $certs = implode(', ', $expires);
        if ($certinfo['error']) {
            $error = get_string('idp_metadata_error', 'auth_musaml', $certinfo['error']);
            $certs .= ' ' . $OUTPUT->pix_icon('i/warning', $error);
        }
        $idpurl = new \core\url('/auth/musaml/management/idp.php', ['id' => $idp->id]);
        $row = [
            \core\output\html_writer::link($idpurl, format_string($idp->name)),
            $providerclass::get_name(),
            s($idp->entityid),
            $idp->enabled ? get_string('yes') : get_string('no'),
        ];
        if ($tenancy) {
            $row[] = idp::get_tenant_name($idp);
        }
        $row[] = $certs;
        $table->data[] = $row;
    }
    echo \core\output\html_writer::table($table);
}

echo $OUTPUT->footer();
