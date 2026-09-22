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

// NOTE: no MOODLE_INTERNAL test here, this is a Behat context file.
// The moodle_url type is required by behat_base signatures, autoloading is not available yet.

use Behat\Mink\Exception\ExpectationException;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
// The `tests/classes` autoloader of core only runs under PHPUnit, Behat needs the files.
require_once(__DIR__ . '/../classes/zitadel_client.php');
require_once(__DIR__ . '/../classes/keycloak_client.php');

/**
 * Behat steps for SAML auth.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class behat_auth_musaml extends behat_base {
    /**
     * Create the service provider certificate without clicking through the form.
     *
     * @Given the auth_musaml service provider certificate exists
     */
    public function sp_certificate_exists(): void {
        \auth_musaml\local\saml::regenerate_sp_certificate();
    }

    /**
     * Register this test site in the Zitadel test instance and add it as an IDP, or skip the scenario.
     *
     * @Given the auth_musaml Zitadel test identity provider :name exists
     * @param string $name
     */
    public function zitadel_idp_exists(string $name): void {
        if (!\auth_musaml\tests\zitadel_client::is_available()) {
            throw new \Moodle\BehatExtension\Exception\SkippedException('TEST_AUTH_MUSAML_ZITADEL_* constants are not defined');
        }
        \auth_musaml\local\saml::regenerate_sp_certificate();
        set_config('curlsecurityblockedhosts', '');
        \auth_musaml\tests\zitadel_client::ensure_saml_app();

        $metadataurl = \auth_musaml\tests\zitadel_client::get_metadata_url();
        $metadata = \auth_musaml\local\idp::load_metadata($metadataurl);
        $providerclass = \auth_musaml\local\provider\base::detect_class($metadata);
        $defaults = $providerclass::get_form_defaults($metadata);
        \auth_musaml\local\idp::create((object)[
            'provider' => $providerclass::get_type(),
            'metadataurl' => $metadataurl,
            'name' => $name,
            'enabled' => 1,
            'mapattr' => $defaults['mapattr'],
            'attrsimple' => $defaults['attrsimple'],
            'metadata' => $metadata,
            'attributes' => $defaults['attributes'],
        ]);
    }

    /**
     * Register this test site in the Keycloak test instance and add it as an IDP, or skip the scenario.
     *
     * @Given the auth_musaml Keycloak test identity provider :name exists
     * @param string $name
     */
    public function keycloak_idp_exists(string $name): void {
        if (!\auth_musaml\tests\keycloak_client::is_available()) {
            $reason = 'TEST_AUTH_MUSAML_KEYCLOAK_* constants are not defined';
            throw new \Moodle\BehatExtension\Exception\SkippedException($reason);
        }
        \auth_musaml\local\saml::regenerate_sp_certificate();
        set_config('curlsecurityblockedhosts', '');
        \auth_musaml\tests\keycloak_client::ensure_saml_app();

        $metadataurl = \auth_musaml\tests\keycloak_client::get_metadata_url();
        $metadata = \auth_musaml\local\idp::load_metadata($metadataurl);
        $providerclass = \auth_musaml\local\provider\base::detect_class($metadata);
        $defaults = $providerclass::get_form_defaults($metadata);
        \auth_musaml\local\idp::create((object)[
            'provider' => $providerclass::get_type(),
            'metadataurl' => $metadataurl,
            'name' => $name,
            'enabled' => 1,
            'mapattr' => $defaults['mapattr'],
            'attrsimple' => $defaults['attrsimple'],
            'metadata' => $metadata,
            'attributes' => $defaults['attributes'],
        ]);
    }

    /**
     * Log in on the Keycloak login page as the test user.
     *
     * @When I log in to Keycloak as the auth_musaml test user
     */
    public function keycloak_login(): void {
        $this->execute('behat_general::wait_until_the_page_is_ready');
        $this->execute('behat_forms::i_set_the_field_to', ['username', \auth_musaml\tests\keycloak_client::TEST_USERNAME]);
        $this->execute('behat_forms::i_set_the_field_to', ['password', \auth_musaml\tests\keycloak_client::TEST_PASSWORD]);
        $this->execute('behat_general::i_click_on', ['#kc-login', 'css_element']);
        $this->execute('behat_general::wait_until_the_page_is_ready');
    }

    /**
     * Move an identity provider to a tenant, it then belongs to that login page only.
     *
     * @Given the auth_musaml identity provider :idpname belongs to the tenant :idnumber
     * @param string $idpname
     * @param string $idnumber
     */
    public function idp_belongs_to_tenant(string $idpname, string $idnumber): void {
        global $DB;
        $idpid = $DB->get_field('auth_musaml_idp', 'id', ['name' => $idpname], MUST_EXIST);
        $tenantid = $DB->get_field('tool_mutenancy_tenant', 'id', ['idnumber' => $idnumber], MUST_EXIST);
        \auth_musaml\local\idp::update((object)['id' => $idpid, 'tenantid' => $tenantid]);
    }

    /**
     * Change one flag of an identity provider.
     *
     * @Given the auth_musaml identity provider :idpname has :field set to :value
     * @param string $idpname
     * @param string $field
     * @param string $value
     */
    public function idp_field_set(string $idpname, string $field, string $value): void {
        global $DB;
        $idpid = $DB->get_field('auth_musaml_idp', 'id', ['name' => $idpname], MUST_EXIST);
        \auth_musaml\local\idp::update((object)['id' => $idpid, $field => $value]);
    }

    /**
     * Map the Zitadel test account to a Moodle user.
     *
     * @Given the Zitadel test user is mapped to auth_musaml user :username of :idpname
     * @param string $username
     * @param string $idpname
     */
    public function zitadel_user_mapped(string $username, string $idpname): void {
        global $DB;
        $userid = $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);
        $idpid = $DB->get_field('auth_musaml_idp', 'id', ['name' => $idpname], MUST_EXIST);
        \auth_musaml\local\mapping::create((object)[
            'idpid' => $idpid,
            'userid' => $userid,
            'guid' => \auth_musaml\tests\zitadel_client::get_test_user_id(),
        ]);
    }

    /**
     * Allow the mapped user to keep its own authentication method.
     *
     * @Given the auth_musaml mapping of :username allows other authentication
     * @param string $username
     */
    public function mapping_allows_other_auth(string $username): void {
        global $DB;
        $userid = $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);
        $mapping = \auth_musaml\local\mapping::fetch_by_userid($userid);
        \auth_musaml\local\mapping::update((object)['id' => $mapping->id, 'allowotherauth' => 1]);
    }

    /**
     * Log in on the Zitadel login page as the test user.
     *
     * @When I log in to Zitadel as the auth_musaml test user
     */
    public function zitadel_login(): void {
        $this->execute('behat_general::wait_until_the_page_is_ready');
        $this->execute('behat_forms::i_set_the_field_to', ['loginName', TEST_AUTH_MUSAML_ZITADEL_USERNAME]);
        $this->execute('behat_general::i_click_on', ['#submit-button', 'css_element']);
        $this->execute('behat_general::wait_until_the_page_is_ready');
        $this->execute('behat_forms::i_set_the_field_to', ['password', TEST_AUTH_MUSAML_ZITADEL_PASSWORD]);
        $this->execute('behat_general::i_click_on', ['#submit-button', 'css_element']);
        $this->execute('behat_general::wait_until_the_page_is_ready');
        // Zitadel may offer to set up a second factor, skip it.
        $skip = $this->getSession()->getPage()->find('css', '#skip-button, a[href*="skip"], button[name="skip"]');
        if ($skip) {
            $skip->click();
            $this->execute('behat_general::wait_until_the_page_is_ready');
        }
    }

    /**
     * Fill a field with the URL of a metadata fixture served by the test site.
     *
     * @When I set the field :field to the auth_musaml fixture :fixture URL
     * @param string $field
     * @param string $fixture
     */
    public function set_field_to_fixture_url(string $field, string $fixture): void {
        global $CFG;
        if (!file_exists(__DIR__ . '/../fixtures/' . $fixture)) {
            throw new ExpectationException('Unknown fixture ' . $fixture, $this->getSession());
        }
        $url = $CFG->wwwroot . '/auth/musaml/tests/fixtures/' . $fixture;
        $this->execute('behat_forms::i_set_the_field_to', [$field, $url]);
    }

    /**
     * Fill a field with the content of a metadata fixture, the way a downloaded file is pasted.
     *
     * @When I set the field :field to the auth_musaml fixture :fixture XML
     * @param string $field
     * @param string $fixture
     */
    public function set_field_to_fixture_xml(string $field, string $fixture): void {
        $path = __DIR__ . '/../fixtures/' . $fixture;
        if (!file_exists($path)) {
            throw new ExpectationException('Unknown fixture ' . $fixture, $this->getSession());
        }
        $this->execute('behat_forms::i_set_the_field_to', [$field, file_get_contents($path)]);
    }

    #[\Override]
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'identity providers':
                return new moodle_url('/auth/musaml/management/idps.php');
            default:
                throw new Exception('Unrecognised auth_musaml page type "' . $page . '."');
        }
    }

    #[\Override]
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        global $DB;
        $idpid = $DB->get_field('auth_musaml_idp', 'id', ['name' => $identifier], MUST_EXIST);
        switch (strtolower($type)) {
            case 'idp':
                return new moodle_url('/auth/musaml/management/idp.php', ['id' => $idpid]);
            case 'idp attributes':
                return new moodle_url('/auth/musaml/management/attributes.php', ['id' => $idpid]);
            case 'idp mappings':
                return new moodle_url('/auth/musaml/management/users.php', ['id' => $idpid]);
            default:
                throw new Exception('Unrecognised auth_musaml page type "' . $type . '."');
        }
    }
}
