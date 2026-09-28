# Bundled libraries of auth_musaml

This directory holds the third party PHP libraries used by auth_musaml, installed by Composer
and committed to the repository, so the plugin works on sites installed from a ZIP file as well
as on sites that manage plugins with Composer.

Libraries: onelogin/php-saml (4.x branch) and robrichards/xmlseclibs.

## Why the Composer autoloader is not used

Never include `vendor/autoload.php` of this directory. Every Composer autoloader registers itself
in front of all other class loaders:

* the plugin then becomes the Composer root package for the rest of the request,
  `Composer\InstalledVersions::getRootPackage()` returns this directory instead of Moodle and
  core environment checks inspect the wrong installation,
* copies of packages that Moodle installs too (psr/*, symfony/*, ...) are loaded from here
  instead of from the Moodle vendor directory, possibly in a different version,
* the Composer runtime classes of two Composer versions get mixed.

Moodle 5.3 requires admins to run `composer install` in the Moodle root with production flags,
the Moodle vendor directory must stay the only Composer installation that PHP sees.

## How the libraries are loaded

`tool_mulib\local\vendor_loader::register()` reads the maps Composer generates in
`vendor/composer/autoload_classmap.php`, `autoload_psr4.php`, `autoload_namespaces.php` and
`autoload_files.php` and appends a plain class loader after all existing loaders:

* Moodle always wins, a class Moodle can load is never loaded from here,
* Composer runtime state (registered loaders, installed packages, root package) is not touched,
* autoloaded files are included once, shared with Composer through
  `$GLOBALS['__composer_autoload_files']`,
* registering the same directory again does nothing.

The plugin calls it right before the libraries are needed:

```php
\tool_mulib\local\vendor_loader::register(__DIR__ . '/../../vendor');
```

## Upgrading the libraries

Composer runs inside this directory, `composer.json` sets `"vendor-dir": "."`.

1. Check what is outdated:
   ```
   cd public/auth/musaml/vendor
   composer outdated
   ```
2. Update, always without development packages and with an optimised class map:
   ```
   composer update --no-dev --optimize-autoloader --no-plugins --no-scripts
   ```
   To allow a new major version edit the constraint in `composer.json` first.
3. Update the versions in `thirdpartylibs.xml` of the plugin, add or remove libraries there
   when the dependency tree changed (`composer show --tree`).
4. Remove files Composer does not need for runtime only when they are not referenced by the
   generated maps, the maps are the only thing the loader uses.
5. Run the plugin PHPUnit and Behat tests, then commit the whole directory including
   `composer.json`, `composer.lock`, `composer/` and this README.

onelogin/php-saml is used from its 4.x development branch (`dev-4.x-dev` in `composer.json`),
`composer update` moves it to the latest commit of that branch, record the commit in
`thirdpartylibs.xml`. It requires robrichards/xmlseclibs ^3.1.5, version 4 cannot be used
until php-saml allows it.

Do not add the libraries to the Moodle root `composer.json` and do not require
`vendor/autoload.php` anywhere.
