# auth_musaml compared with auth_saml2

Both [auth_saml2](https://github.com/catalyst/moodle-auth_saml2) and auth_musaml log users
in through SAML 2.0, and they can run side by side on one site.

|                                          | auth_saml2                                 | auth_musaml                                           |
|------------------------------------------|--------------------------------------------|-------------------------------------------------------|
| Library                                  | [SimpleSAMLphp](https://simplesamlphp.org) | [php-saml](https://github.com/SAML-Toolkits/php-saml) |
| Maturity                                 | years in production                        | preview release                                       |
| Site settings                            | 48, plus per field mapping and locks       | 2, plus per field locks                               |
| Identity provider settings               | global, shared by all providers            | per provider                                          |
| Attribute mapping                        | global settings, one set for all providers | per provider, with sync and matching rules            |
| User mapping                             | dynamic, worked out during each login      | fixed, stored in a database table                     |
| Bulk user mapping                        | no                                         | CSV file or pasted text, with preview                 |
| Login with another authentication method | site setting, all users or none            | per user mapping                                      |
| Multi-tenancy                            | callbacks for Moodle Workplace             | MuTMS, provider belongs to one tenant                 |
| Delegated administration                 | site admin only                            | capability for user mappings                          |
| Web services                             | no                                         | create and delete user mappings                       |
| Moodle as identity provider              | yes                                        | no                                                    |
| Federation metadata                      | yes                                        | no                                                    |
| Identity provider initiated login        | yes                                        | no, see the readme                                    |
| Bindings                                 | redirect, POST, artifact                   | redirect, POST                                        |
| Certificate expiry check                 | service provider certificate               | both sides, every certificate                         |
| Logging                                  | debug log to file                          | events in the site log                                |

## Pick auth_saml2 when

- You join a federation and read metadata listing many identity providers.
- You need artifact binding, or Moodle acting as an identity provider.
- Your identity provider has a quirk that needs a setting for it.
- You already run it and it works.

## Pick auth_musaml when

- User mappings must survive renames and email changes.
- Each identity provider needs its own mappings and rules.
- Providers belong to tenants.
- A provisioning system should manage user mappings without admin rights.
- You want failures that state a reason and changes recorded as events.

## Using one to log in to the other

auth_saml2 can turn a Moodle site into an identity provider, and auth_musaml can use it.
Useful for testing without a third party service.

On the site holding the user accounts, enable auth_saml2, switch on `moodleidpenabled` and add
the other site's `/auth/musaml/metadata.php` to `moodleidpsplist`.

On the other site add an identity provider with metadata URL `/auth/saml2/idp/metadata.php`.
That provider sends the username as `uid` plus the standard user fields, and its NameID is
the email address, so use `uid` as the user id.

## Running both on one site

They use separate entity IDs, certificates, endpoints and tables, so an identity provider
sees two unrelated services. Watch three things:

- A user account uses one login method at a time.
- Turn off `attemptsignout` in auth_saml2, its logout hook signs out every user, not only
  its own.
- Both bundle the same XML security library and the first one loaded wins, so keep the
  versions compatible.
