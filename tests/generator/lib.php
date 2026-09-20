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

use auth_musaml\local\idp;
use auth_musaml\local\saml;

/**
 * SAML auth data generator.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class auth_musaml_generator extends component_generator_base {
    /** @var int */
    private int $idpcount = 0;

    #[\Override]
    public function reset() {
        $this->idpcount = 0;
    }

    /**
     * Parsed metadata of a fixture file.
     *
     * @param string $fixture file name in tests/fixtures
     * @return array
     */
    public static function get_fixture_metadata(string $fixture = 'zitadel_metadata.xml'): array {
        $xml = file_get_contents(__DIR__ . '/../fixtures/' . $fixture);
        return saml::parse_idp_metadata($xml);
    }

    /**
     * Create IDP without any network access.
     *
     * Metadata comes from a fixture file (metadatafixture) unless 'metadata' array is given,
     * entityid may be overridden to create several IDPs from the same fixture.
     *
     * @param stdClass|array|null $record
     * @return stdClass
     */
    public function create_idp($record = null): stdClass {
        $this->idpcount++;
        $record = (object)(array)$record;

        if (!isset($record->metadata)) {
            $record->metadata = self::get_fixture_metadata($record->metadatafixture ?? 'zitadel_metadata.xml');
        }
        if (isset($record->entityid)) {
            $record->metadata['entityid'] = $record->entityid;
        } else if ($this->idpcount > 1 || idp::fetch_by_entityid($record->metadata['entityid'])) {
            $record->metadata['entityid'] = $record->metadata['entityid'] . '?' . $this->idpcount;
        }
        if (!isset($record->metadataurl)) {
            $record->metadataurl = 'https://idp' . $this->idpcount . '.example.com/saml/metadata';
        }
        if (!isset($record->name)) {
            $record->name = 'IDP ' . $this->idpcount;
        }
        if (!isset($record->enabled)) {
            $record->enabled = 1;
        }
        if (!isset($record->mapattr)) {
            $record->mapattr = 'UserID';
        }
        if (!empty($record->tenant)) {
            global $DB;
            $record->tenantid = $DB->get_field('tool_mutenancy_tenant', 'id', ['idnumber' => $record->tenant], MUST_EXIST);
        }

        return idp::create($record);
    }

    /**
     * Create attribute mapping.
     *
     * @param stdClass|array|null $record idp (name) or idpid, idpattr, userfield, sync, lock, usermapping
     * @return stdClass
     */
    public function create_attribute($record = null): stdClass {
        global $DB;
        $record = (object)(array)$record;

        if (!empty($record->idp)) {
            $record->idpid = $DB->get_field('auth_musaml_idp', 'id', ['name' => $record->idp], MUST_EXIST);
        }
        if (!isset($record->sync)) {
            $record->sync = \auth_musaml\local\attribute::SYNC_NONE;
        }

        return \auth_musaml\local\attribute::create($record);
    }

    /**
     * Create user mapping, the user is created when only a username is given.
     *
     * @param stdClass|array|null $record idp (name) or idpid, user (username) or userid, guid
     * @return stdClass
     */
    public function create_user_mapping($record = null): stdClass {
        global $DB;
        $record = (object)(array)$record;

        if (!empty($record->idp)) {
            $record->idpid = $DB->get_field('auth_musaml_idp', 'id', ['name' => $record->idp], MUST_EXIST);
        }
        if (!empty($record->user)) {
            $user = $DB->get_record('user', ['username' => $record->user]);
            if (!$user) {
                $user = $this->datagenerator->create_user(['username' => $record->user, 'auth' => 'musaml']);
            }
            $record->userid = $user->id;
        }
        if (!isset($record->guid)) {
            $record->guid = 'guid-' . $record->userid;
        }

        return \auth_musaml\local\mapping::create($record);
    }
}
