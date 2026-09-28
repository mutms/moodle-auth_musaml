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
use core\param;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\hidden;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Create IDP, step two: details prefilled from metadata and provider.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idp_create extends form {
    use idp_fields_trait;

    #[\Override]
    protected function definition(): void {
        $extra = $this->get_extra_data();
        $metadata = $extra['metadata'];
        $providerclass = provider::get_class($extra['provider']);

        $source = (new hidden('metadatasource', param::RAW))
            ->set_default($extra['metadatasource']);
        $this->add($source);

        $this->add(new info('entityid', get_string('idp_entityid', 'auth_musaml'), $metadata['entityid'], info::PLAIN));
        $this->add(new info('ssourl', get_string('idp_ssourl', 'auth_musaml'), $metadata['ssourl'], info::PLAIN));
        if ($metadata['attributes']) {
            $label = get_string('idp_advertisedattributes', 'auth_musaml');
            $this->add(new info('advertisedattributes', $label, implode(', ', $metadata['attributes']), info::PLAIN));
        }

        $defaults = $providerclass::get_form_defaults($metadata);
        $this->add_idp_fields($providerclass::get_type(), [
            'provider' => $providerclass::get_type(),
            'name' => $metadata['displayname'] ?? $defaults['name'],
            'mapattr' => $defaults['mapattr'],
            'attrsimple' => $defaults['attrsimple'],
            'enabled' => 1,
            'automap' => 1,
        ]);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('idp_create', 'auth_musaml')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $this->validate_idp_fields($data, $allerrors);
    }
}
