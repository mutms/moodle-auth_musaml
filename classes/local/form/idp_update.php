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
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\hidden;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;

/**
 * Update IDP form.
 *
 * Changing the metadata URL downloads the new metadata during validation.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp_update extends form {
    use idp_fields_trait;

    /** @var array|null parsed metadata when the URL changed */
    private ?array $metadata = null;

    #[\Override]
    protected function definition(): void {
        $current = $this->get_current_data();

        $this->add(new hidden('id'));
        $this->add(new info('entityid', get_string('idp_entityid', 'auth_musaml'), info::PLAIN));

        // Pasted metadata is not stored, only its values, so the box shows the URL or nothing.
        $sourcelabel = get_string('idp_metadatasource', 'auth_musaml');
        $source = (new textarea('metadatasource', $sourcelabel, ['type' => 'rawtext', 'rows' => 3]))
            ->add_help_button('idp_metadatasource', 'auth_musaml');
        $this->add($source);

        $this->add_idp_fields($current['provider']);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('idp_update', 'auth_musaml')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $this->validate_idp_fields($data, $allerrors);

        $idp = $this->get_extra_data()['idp'];
        $source = trim((string)$data['metadatasource']);
        if ($source !== '' && $source !== $idp->metadataurl) {
            try {
                $metadata = idp::load_metadata_source($source);
                $other = idp::fetch_by_entityid($metadata['entityid']);
                if ($other && $other->id != $idp->id) {
                    $allerrors['metadatasource'][] = get_string('error_entityidexists', 'auth_musaml', $metadata['entityid']);
                } else {
                    $this->metadata = $metadata;
                }
            } catch (moodle_exception $e) {
                $allerrors['metadatasource'][] = $e->getMessage();
            }
        }
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
