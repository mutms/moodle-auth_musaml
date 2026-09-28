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
 * Force IDP metadata and certificate refresh.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_musaml\local\form\certificate_refresh;
use auth_musaml\local\idp;
use tool_mulib\muform\handler;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var core_renderer $OUTPUT */
/** @var moodle_page $PAGE */
/** @var moodle_database $DB */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require(__DIR__ . '/../../../config.php');

require_login();

$context = \core\context\system::instance();
require_capability('moodle/site:config', $context);

$id = required_param('id', PARAM_INT);
$idp = $DB->get_record('auth_musaml_idp', ['id' => $id], '*', MUST_EXIST);
if ($idp->metadataurl === '') {
    // The metadata was pasted as XML, there is nowhere to refresh from.
    throw new \core\exception\moodle_exception('error_metadatanourl', 'auth_musaml');
}

$pageurl = new \core\url('/auth/musaml/management/certificate_refresh.php', ['id' => $idp->id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);

$handler = handler::from_request();

$returnurl = new \core\url('/auth/musaml/management/idp.php', ['id' => $idp->id]);

$form = new certificate_refresh($pageurl, $idp);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}
if ($data = $form->get_data()) {
    $error = idp::refresh_metadata($idp);
    if ($error === null) {
        \core\notification::success(get_string('idp_metadata_refreshed', 'auth_musaml'));
    } else {
        \core\notification::error(get_string('idp_metadata_error', 'auth_musaml', $error));
    }
    $handler->submitted($returnurl);
}

$title = get_string('idp_metadata_refresh', 'auth_musaml');
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$handler->render($form);
