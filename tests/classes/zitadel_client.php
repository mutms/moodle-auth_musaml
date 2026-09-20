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

namespace auth_musaml\tests;

/**
 * Zitadel test instance client.
 *
 * Tests use a real Zitadel instance the same way auth_ldap tests use a real LDAP server.
 * Required constants in config.php:
 *   TEST_AUTH_MUSAML_ZITADEL_URL       base URL of the instance
 *   TEST_AUTH_MUSAML_ZITADEL_PAT       personal access token of a service user with ORG_OWNER role
 *   TEST_AUTH_MUSAML_ZITADEL_USERNAME  login name of an existing human user
 *   TEST_AUTH_MUSAML_ZITADEL_PASSWORD  password of that user
 *
 * Every test site gets its own Zitadel project named after the database name and prefix.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class zitadel_client {
    /**
     * Are all constants defined?
     *
     * @return bool
     */
    public static function is_available(): bool {
        return defined('TEST_AUTH_MUSAML_ZITADEL_URL') && defined('TEST_AUTH_MUSAML_ZITADEL_PAT')
            && defined('TEST_AUTH_MUSAML_ZITADEL_USERNAME') && defined('TEST_AUTH_MUSAML_ZITADEL_PASSWORD');
    }

    /**
     * IDP metadata URL.
     *
     * @return string
     */
    public static function get_metadata_url(): string {
        return rtrim(TEST_AUTH_MUSAML_ZITADEL_URL, '/') . '/saml/v2/metadata';
    }

    /**
     * Project name unique to this test site.
     *
     * @return string
     */
    public static function get_project_name(): string {
        global $CFG;
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $CFG->dbname . '_' . $CFG->prefix);
    }

    /**
     * Make sure the project exists and contains one SAML application for the given SP metadata.
     *
     * The application metadata is replaced on every call because test sites regenerate the SP certificate.
     *
     * @return string project id
     */
    public static function ensure_saml_app(): string {
        $name = self::get_project_name();
        $spmetadataxml = self::prepare_sp();

        $projectid = null;
        $result = self::call('POST', '/management/v1/projects/_search', [
            'queries' => [['nameQuery' => ['name' => $name, 'method' => 'TEXT_QUERY_METHOD_EQUALS']]],
        ]);
        foreach ($result['result'] ?? [] as $project) {
            if ($project['name'] === $name) {
                $projectid = $project['id'];
                break;
            }
        }
        if (!$projectid) {
            $result = self::call('POST', '/management/v1/projects', [
                'name' => $name, 'projectRoleAssertion' => false, 'projectRoleCheck' => false,
            ]);
            $projectid = $result['id'];
        }

        $appid = null;
        $apps = self::call('POST', "/management/v1/projects/$projectid/apps/_search", []);
        foreach ($apps['result'] ?? [] as $app) {
            if ($app['name'] === 'moodle' && isset($app['samlConfig'])) {
                $appid = $app['id'];
                break;
            }
        }
        if ($appid) {
            self::call('PUT', "/management/v1/projects/$projectid/apps/$appid/saml_config", [
                'metadataXml' => base64_encode($spmetadataxml),
                'loginVersion' => ['loginV1' => new \stdClass()],
            ]);
        } else {
            self::call('POST', "/management/v1/projects/$projectid/apps/saml", [
                'name' => 'moodle',
                'metadataXml' => base64_encode($spmetadataxml),
                'loginVersion' => ['loginV1' => new \stdClass()],
            ]);
        }

        return $projectid;
    }

    /**
     * Zitadel user id of the test user, this is the value of the UserID attribute.
     *
     * @return string
     */
    public static function get_test_user_id(): string {
        $query = ['loginName' => TEST_AUTH_MUSAML_ZITADEL_USERNAME, 'method' => 'TEXT_QUERY_METHOD_EQUALS'];
        $result = self::call('POST', '/v2/users', ['queries' => [['loginNameQuery' => $query]]]);
        foreach ($result['result'] ?? [] as $user) {
            return $user['userId'];
        }
        throw new \RuntimeException('Zitadel test user not found: ' . TEST_AUTH_MUSAML_ZITADEL_USERNAME);
    }

    /**
     * Give this test site its own service provider entity ID and return its metadata.
     *
     * Every Moodle uses the same wwwroot under PHPUnit, so without this two test sites
     * sharing one Zitadel would fight over the same entity ID, which must be unique in
     * the whole instance, not just in the project.
     *
     * @return string SP metadata XML
     */
    public static function prepare_sp(): string {
        global $CFG;

        $entityid = $CFG->wwwroot . '/auth/musaml/metadata.php?site=' . self::get_project_name();
        set_config('sp_entityid', $entityid, 'auth_musaml');
        return \auth_musaml\local\saml::get_sp_metadata();
    }

    /**
     * Management API call.
     *
     * @param string $method
     * @param string $path
     * @param array|null $body
     * @return array decoded response
     */
    public static function call(string $method, string $path, ?array $body = null): array {
        $ch = curl_init(rtrim(TEST_AUTH_MUSAML_ZITADEL_URL, '/') . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . TEST_AUTH_MUSAML_ZITADEL_PAT,
                'Content-Type: application/json',
            ],
        ]);
        if ($body !== null) {
            // Empty PHP arrays must become JSON objects, the API rejects lists.
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?: new \stdClass()));
        }
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($response === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException("Zitadel API $method $path failed: $status $error $response");
        }
        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }
}
