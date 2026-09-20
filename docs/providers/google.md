# Google Workspace

Class `auth_musaml\local\provider\google`, verified against a real Workspace domain.

Google publishes no metadata URL, the admin console offers a file. Paste its content
into the **Metadata URL or XML** box when adding the identity provider.

## In Google

Admin console, Apps, Web and mobile apps, Add app, **Add custom SAML app**.

1. Name the app, then download the metadata file on the next screen.
2. Service provider details, from the **Service provider** page of this plugin:
   - ACS URL: `https://<site>/auth/musaml/endpoints/acs.php`
   - Entity ID: `https://<site>/auth/musaml/metadata.php`
   - Start URL: leave empty, logins must start at Moodle.
   - Name ID format: **EMAIL**
   - Name ID: **Basic Information > Primary email**
3. Attribute mapping, the names this plugin expects:

   | Google field                      | App attribute |
   |-----------------------------------|---------------|
   | Basic Information > Primary email | `email`       |
   | Basic Information > First name    | `first_name`  |
   | Basic Information > Last name     | `last_name`   |

4. Turn the app **on** for everyone, or for the organisational units that should use it.

## In Moodle

1. Service provider, **Generate certificate**, the values for step 2 above are there.
2. Identity providers, **Add identity provider**, paste the metadata file content.
3. The provider is detected as Google Workspace and these mappings are prefilled:

   | IDP attribute | User field | Sync                | Used for mapping |
   |---------------|------------|---------------------|------------------|
   | `nameid`      | username   | On account creation | yes              |
   | `email`       | email      | On every login      | no               |
   | `first_name`  | firstname  | On every login      | no               |
   | `last_name`   | lastname   | On every login      | no               |

4. Run **Test login** from the Attributes tab to see what arrives before mapping users.

## What the class does

- Detects Google from the entity ID `https://accounts.google.com/o/saml2...`.
- Uses the NameID as the user id and requests the email address format, Google
  rejects anything else.
- Maps the NameID to the username as well, which is what account creation needs, so
  usernames become email addresses.
- Reports no single logout support.

## Quirks

**The user id is an email address.** Google sends the address as NameID and offers no
permanent identifier. Everything else in this plugin assumes a permanent id, this
provider is the exception.

A Workspace administrator can change a user's primary address, and the NameID follows it.
Google keeps the old address as an alias so mail still arrives, but the alias is not what
SAML sends, so the identity provider account id changes with the rename.

Fix the mapping rather than the person: open Mapped users, edit their row and put the new
address in "Identity provider account ID". The Moodle account, its history and its grades
stay as they are.

Left alone, the next login either fails with "No user is mapped to identity provider
account", or, with automatic account creation on, makes a second account and leaves the
first one behind.

**No logout service.** Google has none, so a Moodle logout ends the Moodle session only.

**No metadata URL.** Certificates cannot be refreshed automatically. Google certificates
last five years, and a new file can be pasted on the identity provider settings, or the
new certificate added on the Certificates tab.
