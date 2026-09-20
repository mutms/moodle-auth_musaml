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
 * Add a new IDP.
 *
 * Two steps in one modal: metadata URL first, then details prefilled from the metadata.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\form\idp_create;
use auth_musaml\local\form\idp_create_metadata;
use auth_musaml\local\idp;
use auth_musaml\local\provider\base as provider;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_page $PAGE */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

require_login();
$context = \core\context\system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/auth/musaml/management/idp_create.php');
$PAGE->set_context($context);

$returnurl = new \core\url('/auth/musaml/management/idps.php');

$step2 = optional_param('_qf__auth_musaml_local_form_idp_create', 0, PARAM_BOOL);

if (!$step2) {
    $form = new idp_create_metadata();

    if ($form->is_cancelled()) {
        $form->ajax_form_cancelled($returnurl);
    }

    if ($data = $form->get_data()) {
        $metadata = $form->get_metadata();
        if ($data->provider === 'auto') {
            $providerclass = provider::detect_class($metadata);
        } else {
            $providerclass = provider::get_class($data->provider);
        }
        $form = new idp_create(null, [
            'metadatasource' => $data->metadatasource,
            'metadata' => $metadata,
            'provider' => $providerclass::get_type(),
        ]);
        $form->ajax_form_render();
    }

    $form->ajax_form_render();
}

$metadatasource = required_param('metadatasource', PARAM_RAW);
$providertype = optional_param('provider', 'generic', PARAM_ALPHANUMEXT);
$metadata = idp::load_metadata_source($metadatasource);

$form = new idp_create(null, [
    'metadatasource' => $metadatasource,
    'metadata' => $metadata,
    'provider' => $providertype,
]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $providerclass = provider::get_class($data->provider);
    // Pasted XML has no URL, such an identity provider is never refreshed automatically.
    $data->metadataurl = idp::source_is_url($metadatasource) ? trim($metadatasource) : '';
    $data->metadata = $metadata;
    $data->attributes = $providerclass::get_form_defaults($metadata)['attributes'];
    $idp = idp::create($data);
    $form->ajax_form_submitted(new \core\url('/auth/musaml/management/idp.php', ['id' => $idp->id]));
}

$form->ajax_form_render();
