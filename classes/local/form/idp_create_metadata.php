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
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;

/**
 * Create IDP, step one: metadata URL and provider.
 *
 * Validation downloads and parses the metadata, the result is available via get_metadata().
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp_create_metadata extends form {
    /** @var array|null parsed metadata after successful validation */
    private ?array $metadata = null;

    #[\Override]
    protected function definition(): void {
        $sourcelabel = get_string('idp_metadatasource', 'auth_musaml');
        $source = (new textarea('metadatasource', $sourcelabel, ['type' => 'rawtext', 'rows' => 3]))
            ->set_required(true)
            ->add_help_button('idp_metadatasource', 'auth_musaml');
        $this->add($source);

        $menu = ['auto' => get_string('provider_auto', 'auth_musaml')] + provider::get_menu();
        $provider = (new select('provider', get_string('idp_provider', 'auth_musaml'), $menu))
            ->add_help_button('idp_provider', 'auth_musaml');
        $this->add($provider);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('continue')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        try {
            $metadata = idp::load_metadata_source($data['metadatasource']);
            if (idp::fetch_by_entityid($metadata['entityid'])) {
                $allerrors['metadatasource'][] = get_string('error_entityidexists', 'auth_musaml', $metadata['entityid']);
            } else {
                $this->metadata = $metadata;
            }
        } catch (moodle_exception $e) {
            $allerrors['metadatasource'][] = $e->getMessage();
        }
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
