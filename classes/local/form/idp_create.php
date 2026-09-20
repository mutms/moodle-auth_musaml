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

use auth_musaml\local\provider\base as provider;

/**
 * Create IDP, step two: details prefilled from metadata and provider.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp_create extends \tool_mulib\local\ajax_form {
    use idp_fields_trait;

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $metadata = $this->_customdata['metadata'];
        $providerclass = provider::get_class($this->_customdata['provider']);

        $mform->addElement('hidden', 'metadatasource');
        $mform->setType('metadatasource', PARAM_RAW);
        $mform->setConstant('metadatasource', $this->_customdata['metadatasource']);

        $mform->addElement('static', 'entityidstatic', get_string('idp_entityid', 'auth_musaml'), s($metadata['entityid']));
        $mform->addElement('static', 'ssourlstatic', get_string('idp_ssourl', 'auth_musaml'), s($metadata['ssourl']));
        if ($metadata['attributes']) {
            $mform->addElement(
                'static',
                'attributesstatic',
                get_string('idp_advertisedattributes', 'auth_musaml'),
                s(implode(', ', $metadata['attributes']))
            );
        }

        $this->add_idp_fields($mform);

        $defaults = $providerclass::get_form_defaults($metadata);
        $mform->setDefault('provider', $providerclass::get_type());
        $mform->setDefault('name', $metadata['displayname'] ?? $defaults['name']);
        $mform->setDefault('mapattr', $defaults['mapattr']);
        $mform->setDefault('attrsimple', $defaults['attrsimple']);
        $mform->setDefault('enabled', 1);
        $mform->setDefault('automap', 1);

        $this->add_action_buttons(true, get_string('idp_create', 'auth_musaml'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        return array_merge($errors, $this->validate_idp_fields($data));
    }
}
