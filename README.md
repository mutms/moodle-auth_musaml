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

Logins must start at this site. A response the identity provider sends on its own, without
a request from this site, is refused. Such unsolicited responses are what the application
tiles of Entra, Okta, Google and other portals send by default.

This is on purpose. An unsolicited response carries no proof of which browser asked for it,
so an attacker can post their own valid response to someone else's browser and log it into
the attacker's account. Every accepted response here answers a request this site sent, and
only the Moodle session that sent it can finish the login.

### Application tiles

A tile still works when it opens a URL of this site instead of sending a response. The site
then sends a normal request, the identity provider already knows the user and answers it
without asking for a password, so the user lands in Moodle just as with an unsolicited
response:

- The **Login URL** shown on the page of each identity provider,
  `https://<site>/auth/musaml/login.php?id=<id>`, starts the login at that identity
  provider. It works for identity providers of a tenant too, the visitor is moved to the
  tenant first. Add `&wantsurl=/course/view.php?id=2` to land on a particular page.
- `https://<site>/login/index.php?tenant=<tenant idnumber>` opens the login page of a tenant
  with all its identity providers. With automatic login enabled for the only identity
  provider of the tenant the user goes straight to it.

How a tile is pointed to a URL depends on the product, see the page of the provider in
[docs/providers](docs/providers).

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
