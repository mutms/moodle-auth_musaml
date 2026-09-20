# Microsoft Entra ID

Class `auth_musaml\local\provider\entra`. **Not verified against a real tenant yet**,
report anything that does not match.

Metadata URL: `https://login.microsoftonline.com/<tenant>/federationmetadata/2007-06/federationmetadata.xml`

## What the class does

- Detects a tenant from an entity ID starting with `https://sts.windows.net/`.
- Uses the object identifier claim as the user id, it never changes for a user:
  `http://schemas.microsoft.com/identity/claims/objectidentifier`
- Turns on short attribute names, the claims are long URIs.
- Prefills the usual name, email and username claims.

## In Entra

1. Enterprise applications, New application, Create your own application.
2. Single sign-on, SAML, upload the service provider metadata.
3. Check the claims, the default set includes the object identifier.
