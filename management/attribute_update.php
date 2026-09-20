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
 * Update a synchronised user attribute.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\attribute;
use auth_musaml\local\form\attribute_edit;
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
$attribute = $DB->get_record('auth_musaml_attribute', ['id' => $id], '*', MUST_EXIST);
$idp = idp::fetch($attribute->idpid);

$PAGE->set_url('/auth/musaml/management/attribute_update.php', ['id' => $attribute->id]);
$PAGE->set_context($context);

$returnurl = new \core\url('/auth/musaml/management/attributes.php', ['id' => $idp->id]);

$form = new attribute_edit(null, ['idp' => $idp, 'attribute' => $attribute]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    attribute::update($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
