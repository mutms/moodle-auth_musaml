@auth @auth_musaml @MuTMS @javascript
Feature: SAML identity provider management
  In order to let users log in through SAML
  As an admin
  I need to manage identity providers

  Background:
    Given the auth_musaml service provider certificate exists
    # Fixtures are downloaded from the test site itself, which is usually a private address.
    And the following config values are set as admin:
      | curlsecurityblockedhosts | |

  Scenario: Admin adds an identity provider from metadata with provider detection
    Given I log in as "admin"
    And I am on the "auth_musaml > Identity providers" page
    And I should see "No identity providers have been added yet"
    When I press "Add identity provider"
    And I set the field "Metadata URL or XML" to the auth_musaml fixture "zitadel_metadata.xml" URL
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    Then I should see "https://zitadel.example.com/saml/v2/metadata" in the ".modal-dialog" "css_element"
    And the field "Provider" matches value "Zitadel"
    And the field "Name" matches value "Zitadel"
    And the field "User ID attribute" matches value "UserID"
    When I set the field "Name" to "Company login"
    And I click on "Add identity provider" "button" in the ".modal-dialog" "css_element"
    Then I should see "Company login"
    And I should see "Zitadel"
    And I should see "https://zitadel.example.com/saml/v2/SSO"
    And I should see "ZITADEL SAML response"
    When I follow "Attributes"
    Then I should see "UserName"
    And I should see "FirstName"
    And I should see "SurName"
    And I should see "Email"

  Scenario: Admin adds an identity provider by pasting the metadata file
    Given I log in as "admin"
    And I am on the "auth_musaml > Identity providers" page
    When I press "Add identity provider"
    # Google and others offer a file download and no metadata URL.
    And I set the field "Metadata URL or XML" to the auth_musaml fixture "zitadel_metadata.xml" XML
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the field "Name" to "Pasted login"
    And I click on "Add identity provider" "button" in the ".modal-dialog" "css_element"
    Then I should see "Pasted login"
    And I should see "Pasted XML, not refreshed automatically"
    And I should see "https://zitadel.example.com/saml/v2/SSO"

  Scenario: Admin updates and deletes an identity provider
    Given the following "auth_musaml > idps" exist:
      | name      | mapattr | enabled |
      | Old IDP   | UserID  | 0       |
    And I log in as "admin"
    When I am on the "Old IDP" "auth_musaml > idp" page
    Then I should see "Old IDP"
    When I press "Update identity provider"
    And I set the following fields to these values:
      | Name                    | New IDP                                  |
      | Enabled                 | 1                                        |
      | Custom library settings | {"security": {"wantAssertionsSigned": false}} |
    And I click on "Update identity provider" "button" in the ".modal-dialog" "css_element"
    Then I should see "New IDP"
    And I should see "Less strict than the plugin defaults"
    And I should see "security.wantAssertionsSigned = false"
    When I click on "Actions" "button"
    And I click on "Delete identity provider" "link"
    And I click on "Delete identity provider" "button" in the ".modal-dialog" "css_element"
    Then I should see "No identity providers have been added yet"

  @tool_mutenancy
  Scenario: Locked fields are frozen in the tenant member form even without a mapping
    Given I skip tests if "tool_mutenancy" is not installed
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
    And the following "users" exist:
      | username | firstname | lastname | email           | auth   | tenant |
      | samluser | Sam       | User     | sam@example.com | musaml | TEN1   |
      | manual1  | Man       | Ual      | man@example.com | manual | TEN1   |
    And the following config values are set as admin:
      | field_lock_email     | locked | auth_musaml |
      | field_lock_firstname | locked | auth_musaml |
    And I log in as "admin"
    # The SAML user is not mapped to any identity provider account yet, locks still apply.
    When I am on the "TEN1" "tool_mutenancy > Tenant users" page
    Then I should see "Sam User"
    And I open the action menu in "Sam User" "table_row"
    And I choose "Edit" in the open action menu
    Then the "email" "field" should be disabled
    And the "firstname" "field" should be disabled
    And the "city" "field" should be enabled
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    When I open the action menu in "Man Ual" "table_row"
    And I choose "Edit" in the open action menu
    Then the "email" "field" should be enabled

  Scenario: Admin manages attribute mappings of an identity provider
    Given the following "auth_musaml > idps" exist:
      | name    | mapattr |
      | Some IDP | UserID |
    And I log in as "admin"
    When I am on the "Some IDP" "auth_musaml > idp attributes" page
    Then I should see "No attributes are mapped yet"
    When I press "Add attribute"
    And I set the following fields to these values:
      | User field   | Email address |
      | IDP attribute | Email        |
      | Sync          | On every login |
      | Used for automatic mapping | 1 |
    And I click on "Add attribute" "button" in the ".modal-dialog" "css_element"
    Then I should see "Email address"
    And I should see "On every login"
    # The same user field cannot be mapped twice for one identity provider.
    When I press "Add attribute"
    And I set the following fields to these values:
      | User field    | Email address |
      | IDP attribute | Mail          |
    And I click on "Add attribute" "button" in the ".modal-dialog" "css_element"
    Then I should see "This user field is already mapped"
    And I click on "Cancel" "button" in the ".modal-dialog" "css_element"
    # Username may only be copied when the user account is created.
    When I press "Add attribute"
    And I set the following fields to these values:
      | User field    | Username       |
      | IDP attribute | UserName       |
      | Sync          | On every login |
    And I click on "Add attribute" "button" in the ".modal-dialog" "css_element"
    Then I should see "username can only be copied when the user account is created"
    And I set the field "Sync" to "On account creation"
    And I click on "Add attribute" "button" in the ".modal-dialog" "css_element"
    Then I should see "Username"
    When I click on "Update attribute" "link" in the "Email address" "table_row"
    And I set the field "IDP attribute" to "PrimaryMail"
    And I click on "Update attribute" "button" in the ".modal-dialog" "css_element"
    Then I should see "PrimaryMail"
    When I click on "Delete attribute" "link" in the "PrimaryMail" "table_row"
    And I click on "Delete attribute" "button" in the ".modal-dialog" "css_element"
    Then I should not see "PrimaryMail"
    And I should see "Username"

  Scenario: Admin manages user mappings of an identity provider
    Given the following "auth_musaml > idps" exist:
      | name     | mapattr |
      | Some IDP | UserID  |
    And the following "users" exist:
      | username | firstname | lastname | email             | auth   |
      | mapme    | Map       | Me       | mapme@example.com | manual |
      | already  | Al        | Ready    | al@example.com    | musaml |
    And the following "auth_musaml > user mappings" exist:
      | idp      | user    | guid   |
      | Some IDP | already | guid-1 |
    And I log in as "admin"
    When I am on the "Some IDP" "auth_musaml > idp mappings" page
    Then I should see "Al Ready"
    When I press "Add user mapping"
    And I set the field "User" to "mapme@example.com"
    And I set the field "Identity provider account ID" to "guid-1"
    And I click on "Add user mapping" "button" in the ".modal-dialog" "css_element"
    Then I should see "already mapped to another user"
    When I set the field "Identity provider account ID" to "guid-2"
    And I click on "Add user mapping" "button" in the ".modal-dialog" "css_element"
    Then I should see "Map Me"
    And I should see "guid-2"
    # The account was switched to SAML authentication by default.
    When I am on the "mapme" "user > editing" page
    Then I should see "SAML authentication"
    When I am on the "Some IDP" "auth_musaml > idp mappings" page
    And I click on "Update user mapping" "link" in the "Map Me" "table_row"
    And I set the field "Identity provider account ID" to "guid-3"
    And I set the field "Other auth allowed" to "1"
    And I click on "Update user mapping" "button" in the ".modal-dialog" "css_element"
    Then I should see "guid-3"
    When I click on "Delete user mapping" "link" in the "Map Me" "table_row"
    And I click on "Delete user mapping" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Map Me"
    And I should see "Al Ready"

  Scenario: Admin imports user mappings from pasted CSV data
    Given the following "auth_musaml > idps" exist:
      | name     | mapattr |
      | Some IDP | UserID  |
    And the following "users" exist:
      | username | firstname | lastname | email             | auth   |
      | one      | One       | User     | one@example.com   | musaml |
      | two      | Two       | User     | two@example.com   | manual |
    And I log in as "admin"
    And I am on the "Some IDP" "auth_musaml > idp mappings" page
    When I click on "Actions" "button"
    And I click on "Import user mappings" "link"
    And I set the field "Or paste CSV data" to multiline:
    """
    guid,email
    z-1,one@example.com
    z-2,two@example.com
    z-3,nobody@example.com
    """
    And I press "Continue"
    # The column meaning is guessed from the header row.
    Then I should see "3 rows were read"
    And I press "Continue"
    Then I should see "Import options"
    When I set the following fields to these values:
      | Skip rows without a matching user | 0 |
    And I press "Check the data"
    # A row without a user stops the import until it is skipped.
    Then I should see "Nothing is written until"
    And I should see "No user matches"
    And I should see "Problems: 1"
    And I should not see "Import mappings"
    When I set the following fields to these values:
      | Skip rows without a matching user | 1 |
    And I press "Check the data"
    Then I should see "Ready to import: 2"
    And I should see "Skipped: 1"
    When I press "Import mappings"
    Then I should see "Imported 2 user mappings, skipped 1"
    And I should see "One User"
    And I should see "Two User"
    And I should see "z-1"
    # The manual account was switched to SAML authentication by the import option.
    When I am on the "two" "user > editing" page
    Then I should see "SAML authentication"

  @_file_upload
  Scenario: Tab separated data is recognised and imported
    Given the following "auth_musaml > idps" exist:
      | name     | mapattr |
      | Some IDP | UserID  |
    And the following "users" exist:
      | username | firstname | lastname | email           | auth   |
      | one      | One       | User     | one@example.com | musaml |
    And I log in as "admin"
    And I am on the "Some IDP" "auth_musaml > idp mappings" page
    When I click on "Actions" "button"
    And I click on "Import user mappings" "link"
    # A spreadsheet saves and copies its columns separated by tabs.
    And I upload "auth/musaml/tests/fixtures/user_mappings_tabs.csv" file to "CSV file" filemanager
    And I press "Continue"
    # The separator is detected, nobody has to know a spreadsheet uses tabs.
    Then I should see "1 rows were read"
    And I should see "one@example.com"

  @_file_upload
  Scenario: Admin imports user mappings from an uploaded CSV file
    Given the following "auth_musaml > idps" exist:
      | name     | mapattr |
      | Some IDP | UserID  |
    And the following "users" exist:
      | username | firstname | lastname | email           | auth   |
      | one      | One       | User     | one@example.com | musaml |
      | two      | Two       | User     | two@example.com | musaml |
    And I log in as "admin"
    And I am on the "Some IDP" "auth_musaml > idp mappings" page
    When I click on "Actions" "button"
    And I click on "Import user mappings" "link"
    And I upload "auth/musaml/tests/fixtures/user_mappings.csv" file to "CSV file" filemanager
    And I press "Continue"
    Then I should see "3 rows were read"
    And I press "Continue"
    When I set the following fields to these values:
      | Skip rows without a matching user | 1 |
    And I press "Check the data"
    Then I should see "Ready to import: 2"
    And I should see "Skipped: 1"
    When I press "Import mappings"
    Then I should see "Imported 2 user mappings, skipped 1"
    And I should see "One User"
    And I should see "Two User"

  Scenario: Admin manages identity provider certificates
    Given the following "auth_musaml > idps" exist:
      | name     | mapattr |
      | Some IDP | UserID  |
    And I log in as "admin"
    When I am on the "Some IDP" "auth_musaml > idp" page
    Then I should see "ZITADEL SAML response"
    And I should see "From metadata"
    When I click on "Actions" "button"
    And I click on "Certificate settings" "link"
    And I set the field "Additional signing certificates" to "this is not a certificate"
    And I click on "Save changes" "button" in the ".modal-dialog" "css_element"
    Then I should see "does not contain valid certificates"
    When I set the field "Refresh metadata automatically" to "0"
    And I set the field "Additional signing certificates" to ""
    And I click on "Save changes" "button" in the ".modal-dialog" "css_element"
    Then I should see "Refresh metadata automatically"
    And I should see "No"

  Scenario: Mapping manager works without site administration rights
    Given the following "auth_musaml > idps" exist:
      | name     | mapattr |
      | Some IDP | UserID  |
    And the following "users" exist:
      | username | firstname | lastname | email             | auth   |
      | mapper   | Map       | Manager  | mapper@example.com | manual |
      | target   | Tar       | Get      | target@example.com | musaml |
    And the following "roles" exist:
      | shortname     | name           | archetype |
      | mappingmanager | Mapping manager |           |
    # Identity fields are only searchable for users allowed to see them, as in every user picker.
    And the following "role capability" exists:
      | role                          | mappingmanager |
      | auth/musaml:managemappings    | allow          |
      | moodle/site:viewuseridentity  | allow          |
    And the following "system role assigns" exist:
      | user   | role           |
      | mapper | mappingmanager |
    When I log in as "mapper"
    And I am on the "Some IDP" "auth_musaml > idp mappings" page
    Then I should see "No users are mapped yet"
    # The identity provider settings stay out of reach.
    And I should not see "Update identity provider"
    When I press "Add user mapping"
    And I set the field "User" to "target@example.com"
    And I set the field "Identity provider account ID" to "guid-1"
    And I click on "Add user mapping" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tar Get"
    And I should see "guid-1"
    When I click on "Delete user mapping" "link" in the "Tar Get" "table_row"
    And I click on "Delete user mapping" "button" in the ".modal-dialog" "css_element"
    Then I should see "No users are mapped yet"

  Scenario: Identity provider and mapping changes reach the site logs
    Given the following "auth_musaml > idps" exist:
      | name     | mapattr |
      | Some IDP | UserID  |
    And the following "users" exist:
      | username | firstname | lastname | email             | auth   |
      | mapme    | Map       | Me       | mapme@example.com | musaml |
    And I log in as "admin"
    And I am on the "Some IDP" "auth_musaml > idp mappings" page
    When I press "Add user mapping"
    And I set the field "User" to "mapme@example.com"
    And I set the field "Identity provider account ID" to "guid-1"
    And I click on "Add user mapping" "button" in the ".modal-dialog" "css_element"
    Then I should see "Map Me"
    When I click on "Delete user mapping" "link" in the "Map Me" "table_row"
    And I click on "Delete user mapping" "button" in the ".modal-dialog" "css_element"
    Then I should see "No users are mapped yet"
    # Both changes are in the standard log, where an administrator looks first.
    When I am on site homepage
    And I navigate to "Reports > Logs" in site administration
    And I click on "Get these logs" "button"
    Then I should see "User mapping created"
    And I should see "User mapping deleted"

  Scenario: Invalid custom settings are rejected
    Given the following "auth_musaml > idps" exist:
      | name    |
      | Some IDP |
    And I log in as "admin"
    And I am on the "Some IDP" "auth_musaml > idp" page
    When I press "Update identity provider"
    And I set the field "Custom library settings" to "[1, 2]"
    And I click on "Update identity provider" "button" in the ".modal-dialog" "css_element"
    Then I should see "Custom settings must be a JSON object" in the ".modal-dialog" "css_element"
