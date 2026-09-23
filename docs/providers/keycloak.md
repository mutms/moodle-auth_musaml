# Keycloak

Class `auth_musaml\local\provider\keycloak`, verified against Keycloak 26.4.

Metadata URL: `https://<host>/realms/<realm>/protocol/saml/descriptor`

## What the class does

- Detects a realm from the entity ID `.../realms/<name>` or an SSO URL containing `/protocol/saml`.
- Uses the NameID as the user id, Keycloak has no permanent id attribute.
- Requests the persistent NameID format, see below.
- Allows repeated attribute names, see below.
- Prefills `username`, `email`, `firstName` and `lastName` mappings.

## In Keycloak

1. Create a client from the service provider metadata URL, Clients, Import client.
2. Add protocol mappers for `username`, `email`, `firstName` and `lastName`,
   Keycloak sends no attributes without them.
3. Set Encryption to off, or set the algorithms as described below.

## Quirks

**NameID is the username by default.** A username can be renamed and reused, so the
class asks for the persistent format, which makes Keycloak send an opaque `G-...`
identifier. Leave "Force name ID format" off in the client so the request is honoured.

**Every realm sends roles.** The built in `role_list` client scope sends one attribute
named `Role` per role. php-saml rejects repeated attribute names, so the class turns
that check off for Keycloak. Removing the scope from the client works too.

**Encryption defaults are unreadable.** With an encryption certificate in the service
provider metadata, Keycloak encrypts assertions with RSA-OAEP and SHA-256, which
neither php-saml nor SimpleSAMLphp can decrypt. Either turn client encryption off, or
set the client to `rsa-oaep-mgf1p`, digest `SHA-1` and mask generation `MGF1-SHA1`.
auth_saml2 fails the same way, this is not specific to this plugin.
