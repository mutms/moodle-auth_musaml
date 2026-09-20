@auth @auth_musaml @auth_musaml_zitadel @MuTMS @javascript
Feature: SAML login round trip with the Zitadel test instance
  In order to trust the SAML integration
  As a developer
  I need to log in through a real identity provider

  Background:
    Given the auth_musaml Zitadel test identity provider "Company login" exists
    And the following config values are set as admin:
      | auth | musaml |

  Scenario: Mapped user logs in through Zitadel
    Given the following "users" exist:
      | username | firstname | lastname | email             | auth   |
      | mapped1  | Mapped    | Person   | mapped1@example.com | musaml |
    And the Zitadel test user is mapped to auth_musaml user "mapped1" of "Company login"
    When I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Zitadel as the auth_musaml test user
    # Attributes of the identity provider win, the stale name is replaced at login.
    Then I should see "Test User"
    And I log out

  Scenario: Existing account is mapped automatically at first login
    Given the following "users" exist:
      | username  | firstname | lastname | email                 | auth   |
      | testuser1 | Existing  | Account  | testuser1@example.com | musaml |
    And the following "auth_musaml > attributes" exist:
      | idp           | idpattr  | userfield | sync | usermapping |
      | Company login | UserName | username  | 1    | 1           |
      | Company login | Email    | email     | 2    | 1           |
    And the auth_musaml identity provider "Company login" has "automap" set to "1"
    When I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Zitadel as the auth_musaml test user
    Then I should see "Test User"
    And I log out
    And I log in as "admin"
    And I am on the "Company login" "auth_musaml > idp mappings" page
    Then I should see "testuser1"
    And I should see "Yes"

  Scenario: New account is created at first login
    Given the following "auth_musaml > attributes" exist:
      | idp           | idpattr   | userfield | sync |
      | Company login | UserName  | username  | 1    |
      | Company login | Email     | email     | 2    |
      | Company login | FirstName | firstname | 2    |
      | Company login | SurName   | lastname  | 2    |
    And the auth_musaml identity provider "Company login" has "autocreate" set to "1"
    When I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Zitadel as the auth_musaml test user
    Then I should see "Test User"
    And I log out
    And I log in as "admin"
    And I am on the "Company login" "auth_musaml > idp mappings" page
    Then I should see "Test User"

  Scenario: Attributes are synchronised and locked fields cannot be edited
    # Attribute mappings come from the Zitadel provider defaults, locking is a plugin setting.
    Given the following config values are set as admin:
      | field_lock_email     | locked | auth_musaml |
      | field_lock_firstname | locked | auth_musaml |
      | field_lock_lastname  | locked | auth_musaml |
    And the following "users" exist:
      | username  | firstname | lastname | email             | auth   |
      | testuser1 | Stale     | Name     | stale@example.com | musaml |
    And the Zitadel test user is mapped to auth_musaml user "testuser1" of "Company login"
    When I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Zitadel as the auth_musaml test user
    Then I should see "Test User"
    When I follow "Profile" in the user menu
    And I click on "Edit profile" "link"
    Then the "email" "field" should be disabled
    And the "firstname" "field" should be disabled
    And the "lastname" "field" should be disabled
    And the "city" "field" should be enabled
    And the field "email" matches value "testuser1@example.com"
    And the field "firstname" matches value "Test"
    And the field "lastname" matches value "User"

  Scenario: Manual account logs in with a password and through Zitadel
    Given the following "users" exist:
      | username | firstname | lastname | email              | auth   |
      | dualuser | Dual      | Account  | dual@example.com   | manual |
    And the Zitadel test user is mapped to auth_musaml user "dualuser" of "Company login"
    And the auth_musaml mapping of "dualuser" allows other authentication
    # The local password still works.
    When I log in as "dualuser"
    Then I should see "Dual Account"
    And I log out
    # The same account also arrives through the identity provider.
    When I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Zitadel as the auth_musaml test user
    Then I should see "Dual Account"
    And I should not see "Test User"
    And I log out
    And I log in as "admin"
    And I am on the "dualuser" "user > editing" page
    # Locks and attribute sync only apply to accounts owned by the identity provider.
    Then the field "email" matches value "dual@example.com"

  Scenario: Single identity provider takes over the login page
    Given the auth_musaml identity provider "Company login" has "autologin" set to "1"
    When I visit "/login/index.php"
    # No Moodle login form, the only identity provider takes over.
    Then "loginName" "field" should exist
    And "Username" "field" should not exist
    When I log in to Zitadel as the auth_musaml test user
    Then I should see "No user is mapped to identity provider account"
    # The failed login must not bounce back to the identity provider.
    And I should see "Log in"
    And "Username" "field" should exist
    When I visit "/login/index.php"
    Then "Username" "field" should exist

  Scenario: Logging out ends the Moodle session
    Given the following "users" exist:
      | username  | firstname | lastname | email                 | auth   |
      | testuser1 | Test      | User     | testuser1@example.com | musaml |
    And the Zitadel test user is mapped to auth_musaml user "testuser1" of "Company login"
    When I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Zitadel as the auth_musaml test user
    Then I should see "Test User"
    When I follow "Log out" in the user menu
    Then I should see "Log in"
    And I should not see "Test User"
    # Zitadel refuses SAML logout requests, so the plugin ends the Moodle session only
    # and the identity provider still knows the visitor.
    When I visit "/login/index.php"
    And I follow "Company login"
    Then I should see "Test User"

  Scenario: Logging out with automatic login leaves the visitor logged out
    Given the following "users" exist:
      | username  | firstname | lastname | email                 | auth   |
      | testuser1 | Test      | User     | testuser1@example.com | musaml |
    And the Zitadel test user is mapped to auth_musaml user "testuser1" of "Company login"
    And the auth_musaml identity provider "Company login" has "autologin" set to "1"
    When I visit "/login/index.php"
    And I log in to Zitadel as the auth_musaml test user
    Then I should see "Test User"
    When I follow "Log out" in the user menu
    # Zitadel still knows the visitor, so the login page must not send them back.
    Then "Username" "field" should exist
    And I should not see "Test User"
    When I visit "/login/index.php"
    Then "Username" "field" should exist

  Scenario: Unmapped user is rejected with a clear message
    When I visit "/login/index.php"
    And I follow "Company login"
    And I log in to Zitadel as the auth_musaml test user
    Then I should see "No user is mapped to identity provider account"
    And I should see "Log in"

  Scenario: Admin test login shows the attributes without logging anybody in
    Given I log in as "admin"
    And I am on the "Company login" "auth_musaml > idp" page
    When I click on "Actions" "button"
    And I click on "Test login" "link"
    And I log in to Zitadel as the auth_musaml test user
    Then I should see "This was a test, nobody was logged in"
    And I should see "UserID"
    And I should see "FirstName"
    And I should see "No user is mapped to this identity provider account yet"
