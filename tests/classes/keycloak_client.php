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
 * Keycloak test instance client.
 *
 * Tests use a real Keycloak instance the same way auth_ldap tests use a real LDAP server.
 * Required constants in config.php:
 *   TEST_AUTH_MUSAML_KEYCLOAK_URL       base URL of the instance
 *   TEST_AUTH_MUSAML_KEYCLOAK_PASSWORD  password of the "admin" user of the master realm
 *
 * Every test site gets its own realm named after the database name and prefix, with one
 * SAML client and one human user in it, so parallel sites never see each other.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class keycloak_client {
    /** @var string login name of the test user created in the realm */
    public const TEST_USERNAME = 'musamltester';

    /** @var string password of the test user, complexity does not matter in a test realm */
    public const TEST_PASSWORD = 'Test-User-Pass1!';

    /** @var string|null cached admin access token */
    private static $token = null;

    /**
     * Are all constants defined?
     *
     * @return bool
     */
    public static function is_available(): bool {
        return defined('TEST_AUTH_MUSAML_KEYCLOAK_URL') && defined('TEST_AUTH_MUSAML_KEYCLOAK_PASSWORD');
    }

    /**
     * Realm name unique to this test site.
     *
     * @return string
     */
    public static function get_realm(): string {
        global $CFG;
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $CFG->dbname . '_' . $CFG->prefix);
    }

    /**
     * IDP metadata URL of the realm.
     *
     * @return string
     */
    public static function get_metadata_url(): string {
        return rtrim(TEST_AUTH_MUSAML_KEYCLOAK_URL, '/')
            . '/realms/' . self::get_realm() . '/protocol/saml/descriptor';
    }

    /**
     * Make sure the realm, the SAML client and the test user exist.
     *
     * The client is replaced on every call because test sites regenerate the SP certificate.
     */
    public static function ensure_saml_app(): void {
        $realm = self::get_realm();
        $spmetadataxml = self::prepare_sp();

        if (!self::call('GET', '/admin/realms/' . $realm, null, true)) {
            self::call('POST', '/admin/realms', ['realm' => $realm, 'enabled' => true]);
        }

        // Keycloak turns an SP descriptor into client JSON, so the ACS and SLO URLs,
        // the entity ID and the signing certificate all come from the metadata.
        $client = self::call('POST', "/admin/realms/$realm/client-description-converter", $spmetadataxml);
        $client['name'] = 'moodle';
        $client['attributes'] = array_merge($client['attributes'] ?? [], [
            // A persistent name ID survives a rename, which is what a user mapping needs.
            'saml_name_id_format' => 'persistent',
            // Keycloak defaults to RSA-OAEP with SHA-256, which php-saml cannot decrypt.
            'saml.encryption.keyAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p',
            'saml.encryption.digestMethod' => 'http://www.w3.org/2000/09/xmldsig#sha1',
            'saml.encryption.maskGenerationFunction' => 'http://www.w3.org/2009/xmlenc11#mgf1sha1',
        ]);

        $existing = self::call('GET', "/admin/realms/$realm/clients?clientId=" . rawurlencode($client['clientId']));
        foreach ($existing as $old) {
            self::call('DELETE', "/admin/realms/$realm/clients/" . $old['id']);
        }
        self::call('POST', "/admin/realms/$realm/clients", $client);

        $created = self::call('GET', "/admin/realms/$realm/clients?clientId=" . rawurlencode($client['clientId']));
        $clientid = $created[0]['id'];

        // Keycloak sends no attributes at all without these.
        foreach (['username', 'email', 'firstName', 'lastName'] as $field) {
            self::call('POST', "/admin/realms/$realm/clients/$clientid/protocol-mappers/models", [
                'name' => $field,
                'protocol' => 'saml',
                'protocolMapper' => 'saml-user-property-mapper',
                'config' => [
                    'user.attribute' => $field,
                    'attribute.name' => $field,
                    'attribute.nameformat' => 'Basic',
                ],
            ]);
        }

        self::ensure_test_user();
    }

    /**
     * Give this test site its own service provider entity ID and return its metadata.
     *
     * Every Moodle uses the same wwwroot under PHPUnit, so without this two test sites
     * sharing one Keycloak would fight over the same client.
     *
     * @return string SP metadata XML
     */
    public static function prepare_sp(): string {
        global $CFG;

        $entityid = $CFG->wwwroot . '/auth/musaml/metadata.php?site=' . self::get_realm();
        set_config('sp_entityid', $entityid, 'auth_musaml');
        return \auth_musaml\local\saml::get_sp_metadata();
    }

    /**
     * Make sure the human test user exists with a known password.
     */
    public static function ensure_test_user(): void {
        $realm = self::get_realm();

        $existing = self::call('GET', "/admin/realms/$realm/users?username=" . self::TEST_USERNAME . '&exact=true');
        foreach ($existing as $old) {
            self::call('DELETE', "/admin/realms/$realm/users/" . $old['id']);
        }

        self::call('POST', "/admin/realms/$realm/users", [
            'username' => self::TEST_USERNAME,
            'email' => self::TEST_USERNAME . '@example.com',
            'emailVerified' => true,
            'firstName' => 'Keycloak',
            'lastName' => 'Tester',
            'enabled' => true,
            'credentials' => [
                ['type' => 'password', 'value' => self::TEST_PASSWORD, 'temporary' => false],
            ],
        ]);
    }

    /**
     * Delete the realm of this test site, used when a run should leave nothing behind.
     */
    public static function delete_realm(): void {
        self::call('DELETE', '/admin/realms/' . self::get_realm(), null, true);
    }

    /**
     * Admin REST API call.
     *
     * @param string $method
     * @param string $path
     * @param array|string|null $data array is sent as JSON, string as XML
     * @param bool $allowfailure return null instead of throwing
     * @return mixed decoded response, true when the body is empty
     */
    private static function call(string $method, string $path, $data = null, bool $allowfailure = false) {
        $url = rtrim(TEST_AUTH_MUSAML_KEYCLOAK_URL, '/') . $path;

        $headers = ['Authorization: Bearer ' . self::get_token()];
        $body = null;
        if (is_array($data)) {
            $headers[] = 'Content-Type: application/json';
            $body = json_encode($data);
        } else if (is_string($data)) {
            $headers[] = 'Content-Type: application/xml';
            $body = $data;
        }

        $response = self::request($method, $url, $headers, $body, $status);
        if ($status >= 400) {
            if ($allowfailure) {
                return null;
            }
            throw new \RuntimeException("Keycloak $method $path failed with $status: $response");
        }
        if (trim((string)$response) === '') {
            return true;
        }
        return json_decode($response, true);
    }

    /**
     * Access token of the admin user.
     *
     * @return string
     */
    private static function get_token(): string {
        if (self::$token !== null) {
            return self::$token;
        }
        $url = rtrim(TEST_AUTH_MUSAML_KEYCLOAK_URL, '/') . '/realms/master/protocol/openid-connect/token';
        $body = http_build_query([
            'client_id' => 'admin-cli',
            'grant_type' => 'password',
            'username' => 'admin',
            'password' => TEST_AUTH_MUSAML_KEYCLOAK_PASSWORD,
        ]);
        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        $response = self::request('POST', $url, $headers, $body, $status);
        $decoded = json_decode((string)$response, true);
        if ($status >= 400 || empty($decoded['access_token'])) {
            throw new \RuntimeException('Cannot authenticate with Keycloak: ' . $response);
        }
        self::$token = $decoded['access_token'];
        return self::$token;
    }

    /**
     * Plain cURL request, the Moodle curl class is not available in all test contexts.
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string|null $body
     * @param int|null $status filled with the HTTP status
     * @return string
     */
    private static function request(string $method, string $url, array $headers, ?string $body, &$status): string {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = (string)curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }
}
