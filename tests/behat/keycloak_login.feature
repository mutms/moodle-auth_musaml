@auth @auth_musaml @auth_musaml_keycloak @MuTMS @javascript
Feature: SAML login round trip with the Keycloak test instance
  In order to trust the SAML integration
  As a developer
  I need to log in and out through an identity provider that accepts logout requests

  Background:
    Given the auth_musaml Keycloak test identity provider "Company login" exists
    And the following config values are set as admin:
      | auth | musaml |

  Scenario: Existing account is mapped automatically and the attributes are synchronised
    Given the following "users" exist:
      | username      | firstname | lastname | email                     | auth   |
      | musamltester  | Stale     | Name     | musamltester@example.com  | musaml |
    And the auth_musaml identity provider "Company login" has "automap" set to "1"
    When I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Keycloak as the auth_musaml test user
    # Attributes of the identity provider win, the stale name is replaced at login.
    Then I should see "Keycloak Tester"
    And I log in as "admin"
    And I am on the "Company login" "auth_musaml > idp mappings" page
    # Keycloak has no permanent id attribute, the persistent NameID is used instead.
    Then I should see "musamltester"

  Scenario: Single logout ends the session on both sides
    Given the following "users" exist:
      | username      | firstname | lastname | email                    | auth   |
      | musamltester  | Keycloak  | Tester   | musamltester@example.com | musaml |
    And the auth_musaml identity provider "Company login" has "automap" set to "1"
    And I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Keycloak as the auth_musaml test user
    And I should see "Keycloak Tester"
    # The logout hook sends a logout request, Keycloak accepts it and sends us back.
    When I follow "Log out" in the user menu
    Then I should see "Log in"
    And "Username" "field" should exist
    # The identity provider session is gone too, a new login asks for credentials again.
    When I visit "/login/index.php"
    And I follow "Company login"
    Then "#kc-form-login" "css_element" should exist

  @tool_mutenancy
  Scenario: Tenant identity provider logs a user in through the tenant login page
    Given I skip tests if "tool_mutenancy" is not installed
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
    And the following "users" exist:
      | username     | firstname | lastname | email                    | auth   | tenant |
      | musamltester | Keycloak  | Tester   | musamltester@example.com | musaml | TEN1   |
    And the auth_musaml identity provider "Company login" belongs to the tenant "TEN1"
    And the auth_musaml identity provider "Company login" has "automap" set to "1"
    # The main login page does not offer a tenant identity provider.
    When I visit "/login/index.php?tenant=0"
    Then I should not see "Company login"
    # The tenant login page does, and the response is accepted even though the endpoint
    # that receives it knows no tenant.
    When I visit "/login/index.php?tenant=TEN1"
    And I follow "Company login"
    And I log in to Keycloak as the auth_musaml test user
    Then I should see "Keycloak Tester"

  @tool_mutenancy
  Scenario: Tenant identity provider creates the new account in its own tenant
    Given I skip tests if "tool_mutenancy" is not installed
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
    And the auth_musaml identity provider "Company login" belongs to the tenant "TEN1"
    And the auth_musaml identity provider "Company login" has "autocreate" set to "1"
    When I visit "/login/index.php?tenant=TEN1"
    And I follow "Company login"
    And I log in to Keycloak as the auth_musaml test user
    # Nobody had an account, the attributes of the identity provider made one.
    Then I should see "Keycloak Tester"
    And I follow "Log out" in the user menu
    When I log in as "admin"
    And I am on the "TEN1" "tool_mutenancy > Tenant users" page
    Then I should see "Keycloak Tester"
