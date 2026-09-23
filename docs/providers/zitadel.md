# Zitadel

Class `auth_musaml\local\provider\zitadel`, verified against Zitadel 4.17.

Metadata URL: `https://<host>/saml/v2/metadata`

## What the class does

- Detects Zitadel from `/saml/v2/metadata` in the entity ID.
- Uses the `UserID` attribute as the user id, it is permanent.
- Prefills `UserName`, `Email`, `FirstName` and `SurName` mappings.
- Reports no single logout support.

## In Zitadel

1. Create a project, then an Application of type SAML.
2. Give it the service provider metadata URL.
3. Turn "Use new Login UI" off, the new one does not carry SAML attributes yet.

## Quirks

**Logout is refused.** Zitadel answers a logout request with `RequestDenied`, so a
Moodle logout ends the Moodle session only.

**Attributes are fixed.** Zitadel sends `UserID`, `UserName`, `Email`, `FirstName`,
`SurName` and `FullName`, there is nothing to configure.
