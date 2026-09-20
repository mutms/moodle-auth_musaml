# Generic SAML 2.0

Class `auth_musaml\local\provider\generic`, the fallback for any product without a
class of its own. It changes nothing: no detection, no prefilled attributes, no
library settings.

Use it when the metadata is not recognised, then map the attributes by hand. Run a
test login from the Attributes tab first, it lists everything the identity provider
sends and each value can be mapped with one click.

If the product turns out to need the same fix on every site, that belongs in a
provider class rather than in each site's custom library settings.
