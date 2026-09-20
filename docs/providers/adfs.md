# Active Directory Federation Services

Class `auth_musaml\local\provider\adfs`. **Not verified against a real server yet**,
report anything that does not match.

Metadata URL: `https://<host>/FederationMetadata/2007-06/FederationMetadata.xml`

## What the class does

- Detects ADFS from the metadata path.
- Uses the primary SID claim as the user id, it survives a rename:
  `http://schemas.microsoft.com/ws/2008/06/identity/claims/primarysid`
- Turns on short attribute names, the claims are long URIs.
- Prefills the usual name, email and username claims.

## In ADFS

1. Add a Relying Party Trust from the service provider metadata URL.
2. Add claim rules for the primary SID, the account name, the email address and the names.
