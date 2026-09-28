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
 * IDP details page.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\idp;
use auth_musaml\local\provider\base as provider;
use auth_musaml\local\saml;
use tool_mulib\output\muform\dialog\button;
use tool_mulib\output\muform\dialog\link;
use tool_mulib\output\entity_details;
use tool_mulib\output\header_actions;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
/** @var core_renderer $OUTPUT */
/** @var moodle_page $PAGE */
/** @var moodle_database $DB */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();
require_capability('moodle/site:config', \core\context\system::instance());

$idp = $DB->get_record('auth_musaml_idp', ['id' => $id], '*', MUST_EXIST);

$pageurl = new \core\url('/auth/musaml/management/idp.php', ['id' => $idp->id]);
idp::setup_page($idp, $pageurl, 'idp_details');

$actions = new header_actions(get_string('actions'));

$url = new \core\url('/auth/musaml/management/idp_update.php', ['id' => $idp->id]);
$button = new button($url, get_string('idp_update', 'auth_musaml'));
$button->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_RELOAD);
$button->set_form_size('xl');
$actions->add_button($button);

// Metadata pasted as XML has no URL to refresh from, new certificates are pasted by hand.
if ($idp->metadataurl !== '') {
    $url = new \core\url('/auth/musaml/management/certificate_refresh.php', ['id' => $idp->id]);
    $link = new link($url, get_string('idp_metadata_refresh', 'auth_musaml'), '');
    $link->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_RELOAD);
    $link->set_form_size('lg');
    $actions->get_dropdown()->add_dialog($link);
}

$url = new \core\url('/auth/musaml/management/certificate_edit.php', ['id' => $idp->id]);
$link = new link($url, get_string('idp_certificates_edit', 'auth_musaml'), '');
$link->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_RELOAD);
$link->set_form_size('lg');
$actions->get_dropdown()->add_dialog($link);

if ($idp->enabled && saml::has_sp_certificate()) {
    $url = new \core\url('/auth/musaml/login.php', ['id' => $idp->id, 'test' => 1, 'sesskey' => sesskey()]);
    $actions->get_dropdown()->add_item(get_string('test_login', 'auth_musaml'), $url);
}

$actions->get_dropdown()->add_divider();

$url = new \core\url('/auth/musaml/management/idp_delete.php', ['id' => $idp->id]);
$link = new link($url, get_string('idp_delete', 'auth_musaml'), '');
$link->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_REDIRECT);
$link->set_form_size('lg');
$link->add_class('text-danger');
$actions->get_dropdown()->add_dialog($link);

$PAGE->add_header_action($OUTPUT->render($actions));

echo $OUTPUT->header();

$certinfo = idp::get_certinfo($idp);
$providerclass = provider::get_class($idp->provider);
$yesno = fn(int $value): string => $value ? get_string('yes') : get_string('no');

if ($certinfo['error']) {
    echo $OUTPUT->notification(get_string('idp_metadata_error', 'auth_musaml', s($certinfo['error'])), 'warning', false);
}
$autologinwarning = idp::get_autologin_warning($idp);
if ($autologinwarning) {
    echo $OUTPUT->notification($autologinwarning, 'warning', false);
}
$unsafe = saml::get_unsafe_overrides(saml::build_settings($idp));
if ($unsafe) {
    $message = get_string('idp_settings_relaxed', 'auth_musaml', s(implode('; ', $unsafe)));
    echo $OUTPUT->notification($message, 'warning', false);
}

$details = new entity_details();
$details->add(get_string('idp_name', 'auth_musaml'), format_string($idp->name));
$details->add(get_string('idp_provider', 'auth_musaml'), $providerclass::get_name());
$details->add(get_string('idp_enabled', 'auth_musaml'), $yesno($idp->enabled));
if (\tool_mulib\local\mulib::is_mutenancy_active()) {
    $details->add(get_string('idp_tenant', 'auth_musaml'), idp::get_tenant_name($idp));
}
$metadatasource = $idp->metadataurl !== ''
    ? s($idp->metadataurl)
    : get_string('idp_metadatapasted', 'auth_musaml');
$details->add(get_string('idp_metadatasource', 'auth_musaml'), $metadatasource);
$details->add(get_string('idp_entityid', 'auth_musaml'), s($idp->entityid));
$details->add(get_string('idp_ssourl', 'auth_musaml'), s($idp->ssourl));
$details->add(get_string('idp_logouturl', 'auth_musaml'), $idp->logouturl ? s($idp->logouturl) : get_string('none'));
$nameidformat = $certinfo['nameidformat'] ? s($certinfo['nameidformat']) : get_string('none');
$details->add(get_string('idp_nameidformat', 'auth_musaml'), $nameidformat);
$details->add(get_string('idp_mapattr', 'auth_musaml'), s($idp->mapattr));
$details->add(get_string('idp_attrsimple', 'auth_musaml'), $yesno($idp->attrsimple));
$details->add(get_string('idp_automap', 'auth_musaml'), $yesno($idp->automap));
$details->add(get_string('idp_autocreate', 'auth_musaml'), $yesno($idp->autocreate));
$prefix = $idp->usernameprefix !== '' ? s($idp->usernameprefix) : get_string('none');
$details->add(get_string('idp_usernameprefix', 'auth_musaml'), $prefix);
$details->add(get_string('idp_autologin', 'auth_musaml'), $yesno($idp->autologin));
if ($idp->customsettingsjson !== null) {
    $json = \core\output\html_writer::tag('pre', s($idp->customsettingsjson));
    $details->add(get_string('idp_customsettings', 'auth_musaml'), $json);
}
$details->add(
    get_string('idp_metadata_fetched', 'auth_musaml'),
    $certinfo['fetched'] ? userdate($certinfo['fetched']) : get_string('idp_metadata_never', 'auth_musaml')
);
$details->add(get_string('idp_autorefresh', 'auth_musaml'), $yesno((int)$certinfo['autorefresh']));
if ($certinfo['validuntil']) {
    $details->add(get_string('idp_metadata_validuntil', 'auth_musaml'), userdate($certinfo['validuntil']));
}
echo $OUTPUT->render($details);

echo $OUTPUT->heading(get_string('idp_certificates', 'auth_musaml'), 3);
$table = new html_table();
$table->head = [
    get_string('sp_cert_subject', 'auth_musaml'),
    get_string('sp_cert_notafter', 'auth_musaml'),
    get_string('sp_cert_fingerprint', 'auth_musaml'),
    get_string('idp_certificate_source', 'auth_musaml'),
];
$sources = [
    'certs' => get_string('idp_certificate_metadata', 'auth_musaml'),
    'extracerts' => get_string('idp_certificate_manual', 'auth_musaml'),
];
foreach ($sources as $key => $source) {
    foreach ($certinfo[$key] as $cert) {
        $notafter = $cert['notafter'] ? userdate($cert['notafter']) : '';
        if ($cert['notafter'] && $cert['notafter'] < time()) {
            $notafter = \core\output\html_writer::span($notafter, 'text-danger');
        }
        $table->data[] = [s($cert['subject']), $notafter, s((string)$cert['fingerprint']), $source];
    }
}
echo \core\output\html_writer::table($table);

echo $OUTPUT->footer();
