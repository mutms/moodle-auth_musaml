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
 * Admin settings for SAML auth.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;
use core\output\html_writer;
use core\url;
use auth_musaml\local\saml;
use auth_musaml\local\userfield;
use core_admin\setting\setting\configcheckbox;
use core_admin\setting\setting\description;
use core_admin\setting\settingpage\settingpage;
use core_admin\setting\tree\category;
use core_admin\setting\tree\externalpage;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var \core_admin\setting\tree\root $ADMIN */
/** @var \core\plugininfo\auth $auth */
/** @var stdClass $CFG */
/** @var string $section */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

$hidden = $auth->is_enabled() === false;

$ADMIN->add('authsettings', new category(
    'auth_musaml_folder',
    new lang_string('pluginname', 'auth_musaml'),
    $hidden
));

// The "Manage authentication" page links to settings.php?section=authsetting<plugin>, so
// the settings page must keep that name, a category there ends with a section error.
$page = new settingpage(
    $section,
    new lang_string('settings'),
    'moodle/site:config',
    $hidden
);

if ($ADMIN->fulltree) {
    // The other two pages are in the same folder, but the folder is not visible from here.
    // Without a certificate the service provider page is the only place to start.
    $spclass = saml::has_sp_certificate() ? 'btn btn-secondary me-2' : 'btn btn-primary me-2';
    $buttons = html_writer::link(
        new url('/auth/musaml/management/sp.php'),
        get_string('sp', 'auth_musaml'),
        ['class' => $spclass]
    );
    $buttons .= html_writer::link(
        new url('/auth/musaml/management/idps.php'),
        get_string('idps', 'auth_musaml'),
        ['class' => 'btn btn-secondary']
    );
    $page->add(new description('auth_musaml/pages', '', html_writer::div($buttons, 'mb-3')));

    $page->add(new configcheckbox(
        'auth_musaml/debug',
        new lang_string('setting_debug', 'auth_musaml'),
        new lang_string('setting_debug_desc', 'auth_musaml'),
        0
    ));

    // Standard field locks, they apply to every user using this authentication method,
    // including users that are not mapped to an identity provider account yet.
    $authplugin = get_auth_plugin('musaml');
    display_auth_lock_options(
        $page,
        'musaml',
        $authplugin->userfields,
        new lang_string('setting_fieldlocks_desc', 'auth_musaml'),
        false,
        false,
        $authplugin->get_custom_user_profile_fields()
    );

    // Fields owned by identity providers are locked on a new site, core defaults them to unlocked.
    foreach (userfield::get_default_locked() as $field) {
        $setting = $page->settings->{'auth_musamlfield_lock_' . $field} ?? null;
        if ($setting) {
            $setting->defaultsetting = 'locked';
        }
    }
}

$ADMIN->add('auth_musaml_folder', $page);

$ADMIN->add('auth_musaml_folder', new externalpage(
    'auth_musaml_sp',
    new lang_string('sp', 'auth_musaml'),
    new url('/auth/musaml/management/sp.php'),
    'moodle/site:config',
    $hidden
));

$ADMIN->add('auth_musaml_folder', new externalpage(
    'auth_musaml_idps',
    new lang_string('idps', 'auth_musaml'),
    new url('/auth/musaml/management/idps.php'),
    'moodle/site:config',
    $hidden
));

// Do not use standard settings page.
$settings = null;
