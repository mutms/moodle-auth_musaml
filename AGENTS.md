# Working in auth_musaml

Instructions for coding agents. The structure carries most of the rules, this file carries
the rest.

## What this is

A SAML 2.0 service provider for Moodle, part of the MuTMS suite. It replaces auth_saml2 on
MuTMS sites: php-saml instead of SimpleSAMLphp, one configuration per identity provider,
multi-tenancy, few settings, and user mappings that user edits cannot break.

## Design decisions

Change these only with the maintainer.

- The auth plugin class stays thin, testable logic lives in `classes/local/`.
- Users are mapped to a permanent identity provider account id in `auth_musaml_user`.
  No matching by email or name at login. `mapattr` accepts `nameid` for products that
  carry the id there.
- Product behaviour belongs in a provider class, not in a setting. Site values are derived
  from Moodle where possible.
- Per provider custom library settings are an escape hatch: any php-saml option may be
  overridden, unsafe values only produce a warning. Never reject an override, never strip
  one, never add a schema. A value worth worrying about belongs in
  `saml::get_unsafe_overrides()`, which lists it on the form and on the detail page, for
  example `strict`, weak signature algorithms and `idp.certFingerprint`.
- Accounts created at login always need an unused username and an unused email, the site
  `allowaccountssameemail` setting is deliberately ignored. Automatic creation assumes one
  person, one address, one account, which keeps automatic mapping and CSV matching
  unambiguous. Sites that need shared addresses map their users by hand.
- Field locking uses the standard `display_auth_lock_options()` settings, so it also covers
  users without a user mapping.
- A tenant identity provider appears on that tenant login page only.
- Users on another authentication method may log in when their mapping allows it. They
  are not attribute synchronised, the site owns their fields.
- Nothing depends on a scheduled task: assertion ids are cleaned on insert, metadata is
  refreshed during logins.

## Layout

| Path                      | Holds                  | Rule                                                    |
|---------------------------|------------------------|---------------------------------------------------------|
| `auth.php`                | auth plugin class      | hooks only, decisions live in `classes/local/`          |
| `login.php`, `endpoints/` | SAML endpoints         | read params, call a helper, redirect                    |
| `metadata.php`            | SP metadata            | a published document, not an endpoint                   |
| `management/*.php`        | admin pages            | one purpose each, ajax pages define `AJAX_SCRIPT` first |
| `classes/local/`          | domain logic           | never decides access                                    |
| `classes/local/provider/` | product knowledge      | one class per product, no site data                     |
| `docs/providers/`         | product setup notes    | one page per provider class, quirks and why             |
| `classes/output/`         | markup shared by pages | no domain logic, no access decisions                    |
| `classes/external/`       | web services           | check a capability, never `moodle/site:config`          |
| `vendor/`                 | php-saml, xmlseclibs   | never edit, update with composer                        |
| `tests/behat/`            | three features         | the Zitadel and Keycloak ones need a real server        |

## Rules that are not visible from the structure

- Use namespaced core classes: `core\exception\*`, `core\url`, `core\context\system`,
  `core\output\html_writer`. `html_table`, `moodle_page` and `advanced_testcase` are not
  namespaced yet.
- Never load `vendor/autoload.php`. It registers `Composer\InstalledVersions` with this
  plugin as the root package and Moodle then looks for its dependencies in our
  `composer.lock`. `saml::init()` registers a namespace autoloader instead.
- Do not call `optional_param()` while the auth plugin is constructed, it runs too early in
  bootstrap and breaks the Behat runner.
- Metadata refreshes happen after a successful login, never before the redirect to the
  identity provider.
- Failures redirect through `login::get_retry_url()`, which turns automatic login off for
  the session. A cookie also stops three automatic redirects within two minutes, because a
  loop that loses the session cannot be stopped by a session flag.
- Seven events exist and that is meant to be enough. Raw SAML XML goes to the error log
  under the debug setting, never into an event.
- Every page states its access first: `require_login()`, then `require_capability()`, then
  the page setup. The check is repeated even when `admin_externalpage_setup()` or
  `idp::setup_page()` would enforce it, so a reviewer sees it at the top of the file.
- A provider class and its page in `docs/providers/` change together, the page says
  what the class does, what to set in the product, and which quirk each tweak answers.
  A fix every site of one product needs belongs in its provider class, not in custom
  library settings.
- Strings live in `lang/en` in alphabetical order.
- Wording: the remote side is an "identity provider account", the local side is a "user",
  and "user account" only where the text is about creating, suspending or switching the
  login itself. Messages shown to the person say "your account".
- Parameter types stay plain, `mpci phpdoc` cannot parse generics such as
  `class-string<x>` in `@param` and then reports the parameter list as incomplete.
  Generics are fine in `@return`.

## Security boundary

Responses are accepted only for a request this session started, assertion ids are stored
until they expire, and `strict` mode is on.

Do not accept unsolicited responses. Identity provider initiated login is not a missing
feature, see the security section in `README.md`.

User mappings decide who may sign in as whom. Web services refuse site administrator
accounts unless the caller is one, and `auth/musaml:managemappings` has no archetypes.

The service provider private key lives in `config_plugins`, encrypted with the site key.
It must never reach the file area, a log or a page.

## Commands

The short commands are [mpd](https://github.com/mutms/mpd)
helpers in `/opt/mpd/assets/vm/project_types/moodle/bin`.
They must be run from the project directory, because PHP is detected based on mpd.env file settings.

| Task           | Command                          | Note                                                    |
|----------------|----------------------------------|---------------------------------------------------------|
| Code checker   | `mpci phpcs public/auth/musaml`  | must be clean                                           |
| Phpdoc checker | `mpci phpdoc public/auth/musaml` | stricter than phpcs, must be clean                      |
| PHPUnit        | `phpunit --filter=auth_musaml`   | after `db/` changes: `phpunit-util --drop`, `--install` |
| Behat          | `behat --tags=@auth_musaml`      | after `db/` changes: `behat-util --drop`, `behat-init`  |
| Purge caches   | `mdl-cache-purge`                | after adding a page, service or capability              |

A new web service or capability needs a `version.php` bump before a site picks it up. On
the development site call `external_update_descriptions()` and `update_capabilities()`.

## Tests against real servers

`tests/classes/keycloak_client.php` drives a real Keycloak over the admin API, so the
PHPUnit and Behat tests need two constants in `config.php`:

```php
define('TEST_AUTH_MUSAML_KEYCLOAK_URL', 'https://keycloak.example.com');
define('TEST_AUTH_MUSAML_KEYCLOAK_PASSWORD', '<password of the master realm admin>');
```

Every test site gets its own realm named after the database name and prefix, with one
client and one user in it, so parallel sites never collide. Without the constants the
tests are skipped. The Behat feature is tagged `@auth_musaml_keycloak` and covers a login
and a single logout, which Keycloak accepts and Zitadel does not.

## External libraries

`vendor/composer.json` tracks the upstream `4.x-dev` branch for `requireDestination`. Run
`composer update` inside `vendor/`, then update `thirdpartylibs.xml`.

## Known traps

- Behat's `I log out` step bypasses `login/logout.php` and never runs the logout hook.
- The shared icon trigger takes an icon name string, not a `pix_icon`.
- Searching accounts by email needs `moodle/site:viewuseridentity`.
- Zitadel refuses logout requests, Google has no logout service. Both say so through
  `supports_slo()`.
- `manual` and `nologin` can never be disabled, `nologin` is refused explicitly.

## Still to do

- Verify the entra and adfs provider classes against real services and store their
  metadata in `tests/fixtures/`. Zitadel, Keycloak, Google and Okta are done.
- Exercise a non-empty username prefix end to end.

## Do not

- Commit anything, stop after a step and let the maintainer review.
- Bump `version.php` for each change.
- Add a setting where a provider class or a default would do.
