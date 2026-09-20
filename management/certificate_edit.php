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
 * Modify IDP certificate details.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\form\certificate_edit;
use auth_musaml\local\idp;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_page $PAGE */
/** @var moodle_database $DB */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$id = required_param('id', PARAM_INT);
$idp = $DB->get_record('auth_musaml_idp', ['id' => $id], '*', MUST_EXIST);

$PAGE->set_url('/auth/musaml/management/certificate_edit.php', ['id' => $idp->id]);
$PAGE->set_context($context);

$returnurl = new \core\url('/auth/musaml/management/idp.php', ['id' => $idp->id]);

$form = new certificate_edit(null, ['idp' => $idp]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    idp::update_certinfo($idp, (bool)$data->autorefresh, (string)$data->extracerts);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
