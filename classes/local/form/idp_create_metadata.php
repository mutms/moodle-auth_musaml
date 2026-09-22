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
use core\exception\moodle_exception;

/**
 * Create IDP, step one: metadata URL and provider.
 *
 * Validation downloads and parses the metadata, the result is available via get_metadata().
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp_create_metadata extends \tool_mulib\local\ajax_form {
    /** @var array|null parsed metadata after successful validation */
    private ?array $metadata = null;

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $label = get_string('idp_metadatasource', 'auth_musaml');
        $mform->addElement('textarea', 'metadatasource', $label, 'rows="3" cols="80"');
        $mform->setType('metadatasource', PARAM_RAW);
        $mform->addRule('metadatasource', null, 'required', null, 'client');
        $mform->addHelpButton('metadatasource', 'idp_metadatasource', 'auth_musaml');

        $menu = ['auto' => get_string('provider_auto', 'auth_musaml')] + provider::get_menu();
        $mform->addElement('select', 'provider', get_string('idp_provider', 'auth_musaml'), $menu);
        $mform->addHelpButton('provider', 'idp_provider', 'auth_musaml');

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            $metadata = idp::load_metadata_source($data['metadatasource']);
            if (idp::fetch_by_entityid($metadata['entityid'])) {
                $errors['metadatasource'] = get_string('error_entityidexists', 'auth_musaml', $metadata['entityid']);
            } else {
                $this->metadata = $metadata;
            }
        } catch (moodle_exception $e) {
            $errors['metadatasource'] = $e->getMessage();
        }
        return $errors;
    }

    /**
     * Parsed metadata, available after get_data() returned data.
     *
     * @return array|null
     */
    public function get_metadata(): ?array {
        return $this->metadata;
    }
}
