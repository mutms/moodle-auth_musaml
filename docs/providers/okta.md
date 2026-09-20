# Okta

Class `auth_musaml\local\provider\okta`, verified against an Okta Integrator free org.

Metadata URL: the **Identity Provider metadata** link on the app's Sign On tab,
`https://<org>.okta.com/app/<id>/sso/saml/metadata`.

## In Okta

Applications, Create App Integration, **SAML 2.0**.

1. General, from the **Identity providers** page of this plugin:
   - Single sign-on URL: `https://<site>/auth/musaml/endpoints/acs.php`,
     tick "Use this for Recipient URL and Destination URL"
   - Audience URI: `https://<site>/auth/musaml/metadata.php`
   - Default RelayState: empty, logins must start at Moodle
   - Name ID format: EmailAddress, Application username: Okta username
2. Attribute statements. The value syntax depends on the org, newer ones want
   `user.profile.<property>`, and the Value field offers a picker:

   | Name        | Value                     |
   |-------------|---------------------------|
   | `id`        | `user.id`                 |
   | `login`     | `user.profile.login`      |
   | `email`     | `user.profile.email`      |
   | `firstName` | `user.profile.firstName`  |
   | `lastName`  | `user.profile.lastName`   |

3. Assignments, assign the app to the people who may use it. Without this the login
   fails at Okta.
4. Sign On tab, copy the Identity Provider metadata URL.

## What the class does

- Detects Okta from the entity ID `http://www.okta.com/...` or an `okta.com` SSO URL.
- Uses the `id` attribute as the user id, see below.
- Prefills `login`, `email`, `firstName` and `lastName` mappings.

## Quirks

**The NameID is not permanent.** Okta sends the application username, normally the email
address, which people change. The `id` attribute statement carries the internal record
id, such as `00u17ugqi3hY9nqOC698`, and never changes, so the class maps users on that.
Without that attribute statement every login fails with "did not send exactly one value
of the user ID attribute".

**Free orgs insist on the Okta Verify app.** To use a normal TOTP application, first
enable Google Authenticator for the whole org under Security, Authenticators, then
enrol it on your own account. The order matters, the personal setup page offers only
what the org allows.
