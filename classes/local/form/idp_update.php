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
use core\exception\moodle_exception;

/**
 * Update IDP form.
 *
 * Changing the metadata URL downloads the new metadata during validation.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp_update extends \tool_mulib\local\ajax_form {
    use idp_fields_trait;

    /** @var array|null parsed metadata when the URL changed */
    private ?array $metadata = null;

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $idp = $this->_customdata['idp'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $idp->id);

        $mform->addElement('static', 'entityidstatic', get_string('idp_entityid', 'auth_musaml'), s($idp->entityid));

        $mform->addElement('textarea', 'metadatasource', get_string('idp_metadatasource', 'auth_musaml'),
            'rows="3" cols="80"');
        $mform->setType('metadatasource', PARAM_RAW);
        $mform->addHelpButton('metadatasource', 'idp_metadatasource', 'auth_musaml');

        $this->add_idp_fields($mform);

        // Pasted metadata is not stored, only its values, so the box shows the URL or nothing.
        $idp = clone($idp);
        $idp->metadatasource = $idp->metadataurl;
        $this->set_data($idp);
        $this->add_action_buttons(true, get_string('idp_update', 'auth_musaml'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $errors = array_merge($errors, $this->validate_idp_fields($data));

        $idp = $this->_customdata['idp'];
        $source = trim((string)$data['metadatasource']);
        if ($source !== '' && $source !== $idp->metadataurl) {
            try {
                $metadata = idp::load_metadata_source($source);
                $other = idp::fetch_by_entityid($metadata['entityid']);
                if ($other && $other->id != $idp->id) {
                    $errors['metadatasource'] = get_string('error_entityidexists', 'auth_musaml', $metadata['entityid']);
                } else {
                    $this->metadata = $metadata;
                }
            } catch (moodle_exception $e) {
                $errors['metadatasource'] = $e->getMessage();
            }
        }
        return $errors;
    }

    /**
     * Parsed metadata if the URL changed.
     *
     * @return array|null
     */
    public function get_metadata(): ?array {
        return $this->metadata;
    }
}
