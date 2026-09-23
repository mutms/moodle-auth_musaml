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

/**
 * SAML authentication lang pack.
 *
 * @package    auth_musaml
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attribute_advertised'] = 'Attributes offered by the identity provider metadata: {$a}';
$string['attribute_create'] = 'Add attribute';
$string['attribute_delete'] = 'Delete attribute';
$string['attribute_delete_info'] = 'The mapping is removed, the values already copied into user profiles are kept.';
$string['attribute_idpattr'] = 'IDP attribute';
$string['attribute_idpattr_help'] = 'Name of the attribute as the identity provider sends it, case sensitive. The special name "nameid" takes the value from the SAML NameID. A test login lists everything that arrives.';
$string['attribute_sync'] = 'Sync';
$string['attribute_sync_help'] = 'Never: used only for automatic mapping. On account creation: copied once. On every login: kept in step with the identity provider, usually with the field locked.';
$string['attribute_sync_none'] = 'Never';
$string['attribute_sync_oncreate'] = 'On account creation';
$string['attribute_sync_onlogin'] = 'On every login';
$string['attribute_update'] = 'Update attribute';
$string['attribute_userfield'] = 'User field';
$string['attribute_userfield_help'] = 'Moodle user field that receives the value. The username is only set when the user account is created.';
$string['attribute_usermapping'] = 'Used for automatic mapping';
$string['attribute_usermapping_help'] = 'The value must match exactly one user.';
$string['attribute_value'] = 'Value';
$string['attributes_none'] = 'No attributes are mapped yet.';
$string['auth_musamldescription'] = 'Users log in through external SAML 2.0 identity providers.';
$string['check_certificates'] = 'SAML certificates';
$string['check_certificates_allok'] = 'All {$a} certificates are valid.';
$string['check_certificates_disabled'] = 'SAML authentication is disabled.';
$string['check_certificates_expired'] = 'Certificate of {$a->name} expired {$a->time} ago.';
$string['check_certificates_none'] = 'No certificates with an expiry date were found.';
$string['check_certificates_ok'] = 'Certificate of {$a->name} expires in {$a->time}.';
$string['check_certificates_soon'] = 'Certificate of {$a->name} expires in {$a->time}.';
$string['error_certificate'] = 'The text does not contain valid certificates.';
$string['error_createemail'] = 'A new user account cannot be created, the email address "{$a}" is not usable.';
$string['error_createmissing'] = 'A new user account cannot be created, the identity provider did not send the "{$a}" field.';
$string['error_createusername'] = 'A new user account cannot be created, the username "{$a}" is not usable.';
$string['error_customsettings_json'] = 'Custom settings are not valid JSON: {$a}';
$string['error_customsettings_object'] = 'Custom settings must be a JSON object with php-saml setting names as keys.';
$string['error_entityidexists'] = 'An identity provider with entity ID "{$a}" already exists.';
$string['error_entityidtoolong'] = 'Entity ID is longer than 255 characters, this identity provider cannot be used.';
$string['error_guidmapped'] = 'This identity provider account is already mapped to another user.';
$string['error_idpunavailable'] = 'This identity provider is not available.';
$string['error_logout'] = 'The identity provider logout message was rejected: {$a}';
$string['error_metadatablocked'] = 'Metadata URL {$a} is blocked by the site cURL security settings, see "HTTP security" in site administration.';
$string['error_metadatadownload'] = 'Metadata download failed: {$a}';
$string['error_metadatanourl'] = 'Pasted metadata cannot be refreshed. Paste new metadata in the identity provider settings.';
$string['error_metadataparse'] = 'Metadata cannot be parsed: {$a}';
$string['error_metadataurl'] = 'Metadata URL must start with http:// or https://';
$string['error_noguid'] = 'The identity provider did not send exactly one value of the user ID attribute "{$a}".';
$string['error_nohttps'] = 'This site does not use https. SAML logins only work with an identity provider on this same site.';
$string['error_nomapping'] = 'No user is mapped to identity provider account "{$a}". Contact the site administrator.';
$string['error_norequest'] = 'No pending login request was found for this response, please start the login again.';
$string['error_otherauth'] = 'Your account uses the "{$a}" authentication method and cannot be used with this identity provider.';
$string['error_replay'] = 'This identity provider response was already used, please log in again.';
$string['error_response'] = 'The identity provider response was rejected: {$a}';
$string['error_spcertinuse'] = 'The service provider certificate cannot be deleted while identity providers are configured.';
$string['error_spmetadata'] = 'Service provider metadata is not valid: {$a}';
$string['error_spnotconfigured'] = 'Service provider certificate has not been created yet.';
$string['error_suspended'] = 'Your account is suspended.';
$string['error_userfieldmapped'] = 'This user field is already mapped for this identity provider.';
$string['error_usermapped'] = 'The user is already mapped to an identity provider.';
$string['error_usernameprefix'] = 'Prefix may contain only lowercase letters, digits, dots, dashes and underscores, at most 50 characters.';
$string['error_usernamesync'] = 'The username can only be copied when the user account is created.';
$string['event_idp_created'] = 'Identity provider created';
$string['event_idp_deleted'] = 'Identity provider deleted';
$string['event_idp_updated'] = 'Identity provider updated';
$string['event_login_failed'] = 'SAML login failed';
$string['event_metadata_refresh_failed'] = 'Identity provider metadata refresh failed';
$string['event_user_mapping_created'] = 'User mapping created';
$string['event_user_mapping_deleted'] = 'User mapping deleted';
$string['idp_advertisedattributes'] = 'Attributes in metadata';
$string['idp_attrsimple'] = 'Simple attribute names';
$string['idp_attrsimple_help'] = 'Also accept the last part of URI attribute names, for example emailaddress for http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress.';
$string['idp_autocreate'] = 'Create user accounts automatically';
$string['idp_autocreate_help'] = 'New user accounts are created for unknown identity provider accounts at first login. Attribute mappings must supply at least username and email.';
$string['idp_autologin'] = 'Automatic login';
$string['idp_autologin_help'] = 'When this is the only identity provider available on the login page, visitors are sent to it immediately instead of seeing the login form.';
$string['idp_autologin_noslo'] = '{$a} does not support single logout, so users stay logged in at the identity provider after logging out here. The next person using the same browser is logged in as them. Do not use automatic login on shared computers.';
$string['idp_automap'] = 'Map existing users automatically';
$string['idp_automap_help'] = 'Map a user with SAML authentication and no mapping at first login, when every attribute marked for automatic mapping matches their profile.';
$string['idp_autorefresh'] = 'Refresh metadata automatically';
$string['idp_autorefresh_help'] = 'Metadata is downloaded again once a day after a successful login, and at once when a response cannot be verified. Turn off to keep the stored certificates.';
$string['idp_certificate_manual'] = 'Added by hand';
$string['idp_certificate_metadata'] = 'From metadata';
$string['idp_certificate_source'] = 'Source';
$string['idp_certificates'] = 'Signing certificates';
$string['idp_certificates_edit'] = 'Certificate settings';
$string['idp_create'] = 'Add identity provider';
$string['idp_created'] = 'Identity provider was added';
$string['idp_customsettings'] = 'Custom library settings';
$string['idp_customsettings_help'] = 'JSON object merged over the php-saml settings, for example {"security": {"wantAssertionsSigned": false}}. Unsafe overrides are allowed but show a warning. Use only when nothing else works.';
$string['idp_delete'] = 'Delete identity provider';
$string['idp_delete_info'] = 'The identity provider, its attribute mappings and {$a} user mappings will be deleted. User accounts are kept.';
$string['idp_deleted'] = 'Identity provider was deleted';
$string['idp_enabled'] = 'Enabled';
$string['idp_entityid'] = 'Entity ID';
$string['idp_extracerts'] = 'Additional signing certificates';
$string['idp_extracerts_help'] = 'Certificates accepted next to those from the metadata, in PEM format one after another. Use this during a key rollover when the identity provider has not published the new certificate yet.';
$string['idp_logouturl'] = 'Single logout URL';
$string['idp_mapattr'] = 'User ID attribute';
$string['idp_mapattr_help'] = 'Attribute holding the permanent user identifier in the identity provider. It must never change for a user and never be reused. The special name "nameid" takes the value from the SAML NameID.';
$string['idp_metadata_error'] = 'Last refresh failed: {$a}';
$string['idp_metadata_fetched'] = 'Metadata fetched';
$string['idp_metadata_never'] = 'Never';
$string['idp_metadata_refresh'] = 'Refresh metadata';
$string['idp_metadata_refresh_info'] = 'Metadata is downloaded again from the metadata URL. Entity ID, endpoints and signing certificates are replaced by the current values.';
$string['idp_metadata_refreshed'] = 'Metadata was refreshed';
$string['idp_metadata_validuntil'] = 'Metadata valid until';
$string['idp_metadatapasted'] = 'Pasted XML, not refreshed automatically';
$string['idp_metadatasource'] = 'Metadata URL or XML';
$string['idp_metadatasource_help'] = 'URL of the identity provider SAML 2.0 metadata, or the metadata XML itself. A URL is refreshed automatically, pasted XML must be replaced by hand when the certificates change.';
$string['idp_metadataurl'] = 'Metadata URL';
$string['idp_metadataurl_help'] = 'Public URL of the identity provider SAML 2.0 metadata. Entity ID, single sign-on URL and signing certificates are read from it and refreshed automatically.';
$string['idp_name'] = 'Name';
$string['idp_name_help'] = 'Displayed on the login page button.';
$string['idp_nameidformat'] = 'NameID format';
$string['idp_provider'] = 'Provider';
$string['idp_provider_help'] = 'Prefills attributes and adjusts library settings for known product quirks. Use Generic for anything else.';
$string['idp_settings_relaxed'] = 'Less strict than the plugin defaults, set by the provider or by the custom library settings: {$a}';
$string['idp_ssourl'] = 'Single sign-on URL';
$string['idp_tenant'] = 'Tenant';
$string['idp_tenant_help'] = 'Offered only on the login page of this tenant. Without a tenant it is offered everywhere.';
$string['idp_update'] = 'Update identity provider';
$string['idp_updated'] = 'Identity provider was updated';
$string['idp_usernameprefix'] = 'Username prefix';
$string['idp_usernameprefix_help'] = 'Added to usernames of created user accounts and used when matching usernames. Keeps usernames unique across identity providers.';
$string['idps'] = 'Identity providers';
$string['idps_none'] = 'No identity providers have been added yet.';
$string['idps_none_info'] = 'Press Add identity provider and paste its metadata URL.';
$string['idps_none_info2'] = 'Send the service provider metadata above to the identity provider. Either step can go first.';
$string['import'] = 'Import user mappings';
$string['import_column_ignore'] = 'Not used';
$string['import_column_number'] = 'Column {$a}';
$string['import_columns'] = 'Column meaning';
$string['import_columns_headers'] = 'First line holds column names';
$string['import_columns_info'] = 'Say what each column holds. {$a} rows were read.';
$string['import_confirm'] = 'Import mappings';
$string['import_counts'] = 'Ready to import: {$a->created}. Skipped: {$a->skipped}. Problems: {$a->error}.';
$string['import_delimiter'] = 'CSV delimiter';
$string['import_delimiter_auto'] = 'Detect automatically';
$string['import_done'] = 'Imported {$a->created} user mappings, skipped {$a->skipped}.';
$string['import_encoding'] = 'Encoding';
$string['import_error_ambiguous'] = 'More than one user matches "{$a}".';
$string['import_error_columntwice'] = 'Each column meaning can be used only once.';
$string['import_error_columnunknown'] = 'This column meaning is not known.';
$string['import_error_empty'] = 'No usable rows were found.';
$string['import_error_guidused'] = 'Identity provider account "{$a}" is already mapped to another user.';
$string['import_error_headers'] = 'Say whether the first line holds column names.';
$string['import_error_invalid'] = 'The row does not hold an identity provider account ID and a user identifier.';
$string['import_error_mapped'] = 'User "{$a}" is already mapped to another identity provider account.';
$string['import_error_mappedsame'] = 'User "{$a}" already has this mapping.';
$string['import_error_missing'] = 'No user matches "{$a}".';
$string['import_error_nodatarows'] = 'Apart from the column names there is no data.';
$string['import_error_noguid'] = 'One column must hold the identity provider account ID.';
$string['import_error_nouser'] = 'One column must identify the user.';
$string['import_error_otherauth'] = 'The user account uses the "{$a}" authentication method, allow other authentication or switch the account.';
$string['import_error_rowsize'] = 'Every row must hold the same number of columns.';
$string['import_error_source'] = 'Upload a file or paste the data.';
$string['import_error_suspended'] = 'User "{$a}" is suspended.';
$string['import_failed'] = 'Nothing was imported, {$a->error} rows have problems.';
$string['import_file'] = 'Source file';
$string['import_more_rows'] = '{$a} more rows are not shown.';
$string['import_options'] = 'Import options';
$string['import_preview'] = 'Check the data';
$string['import_refresh'] = 'Refresh preview';
$string['import_result_created'] = 'Will be imported';
$string['import_result_error'] = 'Problem';
$string['import_result_skipped'] = 'Skipped';
$string['import_skip_ambiguous'] = 'Skip rows matching more than one user';
$string['import_skip_guidused'] = 'Skip rows whose identity provider account is already mapped';
$string['import_skip_info'] = 'Rows with a problem stop the import unless they are skipped here. Nothing is written until the data has been checked.';
$string['import_skip_invalid'] = 'Skip rows with missing values';
$string['import_skip_mapped'] = 'Skip users that are already mapped';
$string['import_skip_missing'] = 'Skip rows without a matching user';
$string['import_skip_unusable'] = 'Skip suspended users and users that cannot use this identity provider';
$string['import_source_info'] = 'Upload a CSV file or paste the data.';
$string['import_text'] = 'Or paste CSV data';
$string['musaml:managemappings'] = 'Manage mappings of users to identity provider accounts';
$string['pluginname'] = 'SAML authentication';
$string['privacy:metadata:auth_musaml_login'] = 'Logins in progress, deleted when the login finishes and after a few minutes at the latest';
$string['privacy:metadata:auth_musaml_login:idpid'] = 'Identity provider';
$string['privacy:metadata:auth_musaml_login:resultjson'] = 'Attributes received from the identity provider';
$string['privacy:metadata:auth_musaml_login:timecreated'] = 'Time the login started';
$string['privacy:metadata:auth_musaml_login:userid'] = 'User the login resolved to';
$string['privacy:metadata:auth_musaml_user'] = 'Mappings of users to identity provider accounts';
$string['privacy:metadata:auth_musaml_user:allowotherauth'] = 'Whether the user may also sign in with another authentication method';
$string['privacy:metadata:auth_musaml_user:automapped'] = 'Whether the mapping was created automatically at first sign in';
$string['privacy:metadata:auth_musaml_user:guid'] = 'Unique user identifier in the identity provider';
$string['privacy:metadata:auth_musaml_user:idpid'] = 'Identity provider';
$string['privacy:metadata:auth_musaml_user:timecreated'] = 'Time the mapping was created';
$string['privacy:metadata:auth_musaml_user:userid'] = 'Moodle user';
$string['privacy:metadata:authsubsystem'] = 'The plugin signs users in through the authentication subsystem.';
$string['provider_adfs'] = 'Active Directory Federation Services';
$string['provider_auto'] = 'Detect from metadata';
$string['provider_entra'] = 'Microsoft Entra ID';
$string['provider_generic'] = 'Generic SAML 2.0';
$string['provider_google'] = 'Google Workspace';
$string['provider_keycloak'] = 'Keycloak';
$string['provider_okta'] = 'Okta';
$string['provider_zitadel'] = 'Zitadel';
$string['setting_debug'] = 'Debug logging';
$string['setting_debug_desc'] = 'Write SAML request and response details to the PHP error log. Do not enable on production sites.';
$string['setting_fieldlocks_desc'] = 'Locked fields cannot be edited by users. Locks apply to every user with SAML authentication, mapped or not.';
$string['sp'] = 'Service provider';
$string['sp_acsurl'] = 'Assertion consumer service URL';
$string['sp_cert'] = 'Certificate';
$string['sp_cert_commonname'] = 'Common name';
$string['sp_cert_create'] = 'Generate certificate';
$string['sp_cert_days'] = 'Validity in days';
$string['sp_cert_delete'] = 'Delete certificate';
$string['sp_cert_delete_info'] = 'The service provider certificate, its private key and the entity ID are deleted.';
$string['sp_cert_download'] = 'Download certificate';
$string['sp_cert_fingerprint'] = 'Certificate fingerprint (SHA-256)';
$string['sp_cert_missing'] = 'Generate the service provider certificate first, identity providers need it during setup.';
$string['sp_cert_notafter'] = 'Certificate expires';
$string['sp_cert_organizationname'] = 'Organisation';
$string['sp_cert_regen'] = 'Regenerate certificate';
$string['sp_cert_regen_warning'] = 'The certificate and private key are replaced immediately. Logins fail until every identity provider has the new service provider metadata.';
$string['sp_cert_subject'] = 'Certificate subject';
$string['sp_entityid'] = 'Entity ID';
$string['sp_entityid_help'] = 'Identifies this site to identity providers, the metadata URL by default. It can only be changed together with the certificate, both must then be registered again in every identity provider.';
$string['sp_metadataurl'] = 'Service provider metadata';
$string['sp_slourl'] = 'Single logout URL';
$string['tab_attributes'] = 'Attributes';
$string['tab_details'] = 'Details';
$string['tab_mappings'] = 'Mapped users';
$string['tenant_all'] = 'All tenants';
$string['test_attributes_info'] = 'Received during a test login {$a} ago.';
$string['test_login'] = 'Test login';
$string['test_login_attributes'] = 'Attributes received';
$string['test_login_nouser'] = 'No user is mapped to this identity provider account yet.';
$string['test_login_success'] = 'The identity provider response was accepted. This was a test, nobody was logged in.';
$string['test_login_user'] = 'Mapped user';
$string['user_mapping_allowotherauth'] = 'Other auth allowed';
$string['user_mapping_allowotherauth_help'] = 'Lets a user with another authentication method, such as manual, also log in through the identity provider.';
$string['user_mapping_automapped'] = 'Mapped automatically';
$string['user_mapping_create'] = 'Add user mapping';
$string['user_mapping_delete'] = 'Delete user mapping';
$string['user_mapping_delete_info'] = 'The mapping between the user and the identity provider account is removed. The user account is kept.';
$string['user_mapping_guid'] = 'Identity provider account ID';
$string['user_mapping_guid_help'] = 'Value of the user ID attribute exactly as the identity provider sends it.';
$string['user_mapping_setauth'] = 'Switch user account to SAML authentication';
$string['user_mapping_setauth_help'] = 'Switches the user account to SAML authentication. Leave unchecked to keep the current method, and allow other authentication instead.';
$string['user_mapping_timecreated'] = 'Mapped';
$string['user_mapping_update'] = 'Update user mapping';
$string['user_mapping_user'] = 'User';
$string['user_mapping_user_help'] = 'Users already mapped to an identity provider are not listed.';
$string['user_mappings'] = 'Mapped users';
$string['user_mappings_none'] = 'No users are mapped yet.';
