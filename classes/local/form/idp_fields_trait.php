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
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\text;
use tool_mulib\muform\element\textarea;

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
     * @param string $providertype provider of the identity provider, decides the single logout warning
     * @param array $defaults default values indexed by element name
     */
    protected function add_idp_fields(string $providertype, array $defaults = []): void {
        $name = (new text('name', get_string('idp_name', 'auth_musaml'), ['width' => 'medium']))
            ->set_required(true)
            ->add_help_button('idp_name', 'auth_musaml');
        $this->add_idp_field($name, $defaults);

        $provider = (new select('provider', get_string('idp_provider', 'auth_musaml'), provider::get_menu()))
            ->add_help_button('idp_provider', 'auth_musaml');
        $this->add_idp_field($provider, $defaults);

        $this->add_idp_field(new checkbox('enabled', get_string('idp_enabled', 'auth_musaml')), $defaults);

        $tenants = idp::get_tenant_menu();
        if ($tenants) {
            $tenantid = (new select('tenantid', get_string('idp_tenant', 'auth_musaml'), $tenants))
                ->add_help_button('idp_tenant', 'auth_musaml');
            $this->add_idp_field($tenantid, $defaults);
        }

        $mapattr = (new text('mapattr', get_string('idp_mapattr', 'auth_musaml'), ['type' => 'rawtext', 'width' => 'medium']))
            ->set_required(true)
            ->add_help_button('idp_mapattr', 'auth_musaml');
        $this->add_idp_field($mapattr, $defaults);

        foreach (['attrsimple', 'automap', 'autocreate'] as $flag) {
            $checkbox = (new checkbox($flag, get_string('idp_' . $flag, 'auth_musaml')))
                ->add_help_button('idp_' . $flag, 'auth_musaml');
            $this->add_idp_field($checkbox, $defaults);
        }

        $prefixlabel = get_string('idp_usernameprefix', 'auth_musaml');
        $usernameprefix = (new text('usernameprefix', $prefixlabel, ['type' => 'rawtext', 'width' => 'small']))
            ->add_help_button('idp_usernameprefix', 'auth_musaml');
        $this->add_idp_field($usernameprefix, $defaults);

        $autologin = (new checkbox('autologin', get_string('idp_autologin', 'auth_musaml')))
            ->add_help_button('idp_autologin', 'auth_musaml');
        $this->add_idp_field($autologin, $defaults);

        $providerclass = provider::get_class($providertype);
        if (!$providerclass::supports_slo()) {
            $warning = get_string('idp_autologin_noslo', 'auth_musaml', $providerclass::get_name());
            $this->add(new inforawhtml('autologinwarning', '', '<div class="alert alert-warning">' . s($warning) . '</div>'));
            $this->get_display_manager()->hide_if('autologinwarning', 'autologin', 'notchecked');
        }

        $settingslabel = get_string('idp_customsettings', 'auth_musaml');
        $customsettings = (new textarea('customsettingsjson', $settingslabel, ['type' => 'rawtext', 'rows' => 6]))
            ->add_help_button('idp_customsettings', 'auth_musaml');
        $this->add_idp_field($customsettings, $defaults);
    }

    /**
     * Add one field with its default.
     *
     * @param \tool_mulib\muform\element $element
     * @param array $defaults
     */
    private function add_idp_field(\tool_mulib\muform\element $element, array $defaults): void {
        if (array_key_exists($element->get_name(), $defaults)) {
            $element->set_default($defaults[$element->get_name()]);
        }
        $this->add($element);
    }

    /**
     * Validate the shared fields.
     *
     * @param array $data
     * @param array $allerrors
     */
    protected function validate_idp_fields(array $data, array &$allerrors): void {
        if (trim((string)$data['mapattr']) === '') {
            $allerrors['mapattr'][] = get_string('required');
        }
        $prefix = trim((string)($data['usernameprefix'] ?? ''));
        if ($prefix !== '' && (\core_text::strlen($prefix) > 50 || !preg_match('/^[a-z0-9._-]+$/', $prefix))) {
            $allerrors['usernameprefix'][] = get_string('error_usernameprefix', 'auth_musaml');
        }
        $error = saml::validate_custom_settings($data['customsettingsjson'] ?? null);
        if ($error !== null) {
            $allerrors['customsettingsjson'][] = $error;
        }
    }
}
