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

namespace auth_musaml\local\form;

use auth_musaml\local\idp;
use auth_musaml\local\provider\base as provider;
use auth_musaml\local\saml;

/**
 * Fields shared by IDP create and update forms.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait idp_fields_trait {
    /**
     * Add the editable IDP fields.
     *
     * @param \MoodleQuickForm $mform
     */
    protected function add_idp_fields(\MoodleQuickForm $mform): void {
        $mform->addElement('text', 'name', get_string('idp_name', 'auth_musaml'), 'size="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'idp_name', 'auth_musaml');

        $mform->addElement('select', 'provider', get_string('idp_provider', 'auth_musaml'), provider::get_menu());
        $mform->addHelpButton('provider', 'idp_provider', 'auth_musaml');

        $mform->addElement('advcheckbox', 'enabled', get_string('idp_enabled', 'auth_musaml'));

        $tenants = idp::get_tenant_menu();
        if ($tenants) {
            $mform->addElement('select', 'tenantid', get_string('idp_tenant', 'auth_musaml'), $tenants);
            $mform->addHelpButton('tenantid', 'idp_tenant', 'auth_musaml');
        }

        $mform->addElement('text', 'mapattr', get_string('idp_mapattr', 'auth_musaml'), 'size="50"');
        $mform->setType('mapattr', PARAM_RAW_TRIMMED);
        $mform->addRule('mapattr', null, 'required', null, 'client');
        $mform->addHelpButton('mapattr', 'idp_mapattr', 'auth_musaml');

        $mform->addElement('advcheckbox', 'attrsimple', get_string('idp_attrsimple', 'auth_musaml'));
        $mform->addHelpButton('attrsimple', 'idp_attrsimple', 'auth_musaml');

        $mform->addElement('advcheckbox', 'automap', get_string('idp_automap', 'auth_musaml'));
        $mform->addHelpButton('automap', 'idp_automap', 'auth_musaml');

        $mform->addElement('advcheckbox', 'autocreate', get_string('idp_autocreate', 'auth_musaml'));
        $mform->addHelpButton('autocreate', 'idp_autocreate', 'auth_musaml');

        $mform->addElement('text', 'usernameprefix', get_string('idp_usernameprefix', 'auth_musaml'), 'size="20"');
        $mform->setType('usernameprefix', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('usernameprefix', 'idp_usernameprefix', 'auth_musaml');

        $mform->addElement('advcheckbox', 'autologin', get_string('idp_autologin', 'auth_musaml'));
        $mform->addHelpButton('autologin', 'idp_autologin', 'auth_musaml');

        $providerclass = provider::get_class($this->_customdata['idp']->provider ?? $this->_customdata['provider'] ?? '');
        if (!$providerclass::supports_slo()) {
            $warning = get_string('idp_autologin_noslo', 'auth_musaml', $providerclass::get_name());
            $mform->addElement('static', 'autologinwarning', '', '<div class="alert alert-warning">' . $warning . '</div>');
            $mform->hideIf('autologinwarning', 'autologin', 'notchecked');
        }

        $mform->addElement(
            'textarea',
            'customsettingsjson',
            get_string('idp_customsettings', 'auth_musaml'),
            ['rows' => 6, 'cols' => 70, 'class' => 'text-monospace']
        );
        $mform->setType('customsettingsjson', PARAM_RAW);
        $mform->addHelpButton('customsettingsjson', 'idp_customsettings', 'auth_musaml');
    }

    /**
     * Validate the shared fields.
     *
     * @param array $data
     * @return array errors
     */
    protected function validate_idp_fields(array $data): array {
        $errors = [];
        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        }
        if (trim($data['mapattr']) === '') {
            $errors['mapattr'] = get_string('required');
        }
        $prefix = trim($data['usernameprefix'] ?? '');
        if ($prefix !== '' && (\core_text::strlen($prefix) > 50 || !preg_match('/^[a-z0-9._-]+$/', $prefix))) {
            $errors['usernameprefix'] = get_string('error_usernameprefix', 'auth_musaml');
        }
        $error = saml::validate_custom_settings($data['customsettingsjson'] ?? null);
        if ($error !== null) {
            $errors['customsettingsjson'] = $error;
        }
        return $errors;
    }
}
