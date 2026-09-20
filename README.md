# MuTMS SAML authentication for Moodle™ LMS

Users log in to Moodle through a SAML 2.0 identity provider, such as Google Workspace,
Keycloak or Okta.

Status: preview release for Moodle 5.3dev.

## Features

- Add as many identity providers as you need, each with its own settings and attribute
  mappings. A tenant identity provider appears on that tenant login page only.
- Each user is mapped to a permanent account id, so username and email changes do not
  affect mapped users.
- Map users to identity provider accounts by hand, from a CSV file, or at first login.
- Copy names, email and other fields from the identity provider, once or at every login.
- Logins that cannot be resolved fail with the reason, no account mapping is ever guessed.

## Requirements

- Moodle 5.3
- tool_mulib
- A site on `https://`

## Setting up

1. Enable **SAML authentication** in *Site administration, Plugins, Authentication*,
   then follow its **Settings** link.
2. Open **Service provider** and press **Generate certificate**.
3. Go to *Identity providers*. Give your identity provider the values shown there, or
   just the service provider metadata URL if it can read one.
4. Press **Add identity provider** and paste the metadata URL of the identity provider,
   or the content of the metadata file it gave you.
5. Check the **Attributes** tab. One attribute must hold the permanent user id.
6. Use **Test login** to see what the identity provider sends. Nobody is logged in.

## Mapping users

A user can be mapped to an identity provider account in four ways:

- By hand, on the **Mapped users** tab.
- In bulk, from a CSV file or pasted text. You see what will happen before anything is saved.
- Automatically at first login, if every attribute you marked for matching points at one user.
- By creating the user account at first login, if the identity provider sends username, email,
  first name and last name.

A user account that keeps another login method, such as a manual admin account, can also use
the identity provider. Tick **Other auth allowed** on its mapping.

## Letting other systems map users

Give a role the `auth/musaml:managemappings` capability. It allows managing user mappings
and nothing else, so a user provisioning system does not need admin rights. Two web
services use the same capability.

## Identity providers

One page per product in [docs/providers](docs/providers), with what the plugin sets
for it and the quirks to expect. Anything else uses the generic provider.

## Good to know

- **Locked user fields** stop users editing fields the identity provider owns.
- **Custom library settings** accept any php-saml option as JSON. Unsafe values are allowed,
  but you get a warning.
- **Automatic login** skips the login page when there is only one identity provider. A
  failed login always returns to the normal login page.

## Security

Logins must start at this site, so the application tiles in Entra, Okta and Google portals
do not work. This is on purpose.

A login starting at the identity provider lets anyone with a valid assertion put another
person's browser into their account, which is considered to be a security issue.

## Compared with auth_saml2

Both plugins do SAML logins and suit different sites. See [COMPARISON.md](COMPARISON.md).

## AI disclosure

Parts of this plugin were written with the help of Claude (Anthropic). A human
maintainer reviewed, corrected and accepted everything before it was committed.
The design decisions and the final code are the maintainer's own.

## License

Copyright (C) 2026 Petr Skoda. [GPL-3.0](LICENSE) or later.

---

> MuTMS is an independent open-source project, not affiliated with Moodle HQ.
