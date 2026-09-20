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
 * Create a synchronised user attribute for one IDP.
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

$idpid = required_param('idpid', PARAM_INT);
$idpattr = optional_param('idpattr', '', PARAM_RAW_TRIMMED);
$idp = $DB->get_record('auth_musaml_idp', ['id' => $idpid], '*', MUST_EXIST);

$PAGE->set_url('/auth/musaml/management/attribute_create.php', ['idpid' => $idp->id]);
$PAGE->set_context($context);

$returnurl = new \core\url('/auth/musaml/management/attributes.php', ['id' => $idp->id]);

$form = new attribute_edit(null, ['idp' => $idp, 'idpattr' => $idpattr]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    attribute::create($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
