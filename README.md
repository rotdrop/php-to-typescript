# PHP to TypeScript

Composer library extracted from
[`rotdrop/nextcloud-app-build-system`](https://github.com/rotdrop/nextcloud-app-build-system/tree/e68e6b70fdf3347f7448b56cbc3c260429ecc159/scripts/php-to-typescript),
commit `e68e6b70fdf3347f7448b56cbc3c260429ecc159`.
All 25 PHP files from `scripts/php-to-typescript/` are now in `src/`.
The original `OCA\RotDrop\DevScripts\PhpToTypeScript` namespace, class names,
license headers, and Nextcloud/toolkit integrations are preserved. The only
source adjustments are parentheses around two `new ReflectionClass(...)`
expressions so they also parse before PHP 8.4.

Requires PHP 8.2 or newer. The package uses Spatie's TypeScript transformer 2.x
(not the older CAFEVDB fork), Symfony Console/Finder 7.x, and phpDocumentor's
type resolver. Licensed under AGPL-3.0-or-later; see [LICENSE](LICENSE).

## Installation

Until a release is published on Packagist, configure this VCS repository in
the consuming project's Composer manifest:

```sh
composer config repositories.rotdrop-php-to-typescript vcs https://github.com/rotdrop/php-to-typescript
composer require --dev 'rotdrop/php-to-typescript:*@dev'
```

Once a release is available, use its version constraint instead of `*@dev`.
Composer autoloads the existing namespace; no manual `addPsr4()` is needed for
the converter.

## Usage and preserved integrations

This is a library, not a standalone executable. Keep the application's
existing PHP wrapper, autoloaders, and Nextcloud console bootstrap.
`PhpToTypeScript` remains a Symfony Console command. For example, an
application-owned wrapper for conversion **without database metadata** can use:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
// Load the application's PHP classes and toolkit here, as in its existing wrapper.

use OCA\RotDrop\DevScripts\PhpToTypeScript\Collectors\DTOCollector;
use OCA\RotDrop\DevScripts\PhpToTypeScript\PhpToTypeScript;
use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$command = new PhpToTypeScript(
    devScriptsFolder: __DIR__,
    collectors: [DTOCollector::class, EnumCollector::class],
);
exit($command->run(new ArgvInput(), new ConsoleOutput()));
```

Pass the **application's development-scripts directory**, not this package's
`src/` directory, as `devScriptsFolder`. Invoke the wrapper with, for example:

```sh
php dev-scripts/php-to-typescript.php \
  --source-dir=lib --output-prefix=src/types --ns-prefix='OCA\MyApp'
```

`--source-dir` and `--exclude` are repeatable. `--constants=constants` (default)
emits literal constants; `--constants=properties` emits literal-typed
properties. `--as-modules` also generates `php-modules/` alongside
`php-types.d.ts`. Keep the existing `--scoped-ns-prefix` and `scopedNamespaces`
configuration when using php-scoper. Supply `--ns-prefix` explicitly, as in
the existing build-system invocation.

For example, `--ns-prefix='OCA\MyApp' --scoped-ns-prefix='Wrapped'` recognizes
DTOs extending
`OCA\MyApp\Wrapped\OCA\RotDrop\Toolkit\DTO\AbstractDTO`.
Without `--scoped-ns-prefix`, only DTOs extending the unscoped
`OCA\RotDrop\Toolkit\DTO\AbstractDTO` are recognized.

Host requirements are intentionally unchanged:

- Discovered application classes and their dependencies must be autoloadable.
  The application supplies its toolkit, including its `Toolkit\DTO\AbstractDTO`;
  this package does not bundle it.
- The default collectors include `DatabaseEntityCollector`. With
  `--as-modules`, this triggers `GenerateEntityMetadata`, which needs the
  application's Doctrine entity manager. Supply it through the
  `entityManagerFactory` constructor argument, a `Closure` returning the
  entity manager; any application specific bootstrapping (user session,
  credentials, ...) belongs into that closure. Do not commit credentials.
- Applications not using that metadata integration should explicitly select
  non-database collectors, as in the example. No Nextcloud stubs
  are supplied by this package.
- Module/metadata generation needs a `ConsoleOutputInterface` implementation
  supporting output sections, such as `ConsoleOutput`.
- Existing Carbon/UUID type replacements remain conditional on those classes
  being available from the application.

## Build-system migration (separate repository change)

The following changes belong in `rotdrop/nextcloud-app-build-system` and its
application consumers; they are not applied by installing this repository:

1. In the build-system's `scripts/` Composer environment, run the installation
   commands above. Keep its existing Nextcloud OCP patch/plugin setup.
2. Keep loading `scripts/vendor/autoload.php` in application wrappers, together
   with their application, toolkit, scoped-dependency, and Nextcloud bootstrap
   code. Remove the manual converter `addPsr4()` pointing at
   `lib/scripts/php-to-typescript/`. Keep unrelated toolkit namespace mappings
   and preserve constructor arguments and collector choices.
3. Remove the bundled `scripts/php-to-typescript/` tree once consumers use
   Composer's package autoloading. Imports continue to use
   `OCA\RotDrop\DevScripts\PhpToTypeScript`; collectors and attributes retain
   their current `Collectors\` and `Attributes\` subnamespaces.
4. In `makefile/ts-types-files.mk`, replace the prerequisite wildcard for the old
   bundled directory with the package manifest and **all** PHP sources under
   `$(DEV_LIB_DIR)/scripts/vendor/rotdrop/php-to-typescript/src/`. Account for a
   clean checkout where `vendor/` does not yet exist: retain the existing
   `dev-setup`/Composer installation before invoking the converter. Preserve
   `TYPESCRIPT_CONVERTER`, its arguments, and the Prettier/ESLint steps.
5. Consumers that currently install Spatie and Finder separately under
   `vendor-bin/typescript-transformer/` can remove that duplicate environment
   and its autoloader after switching to this package's dependency graph.
   Compare generated declarations/modules in the actual Nextcloud application
   before removing the old setup.

## Local checks

```sh
composer install
composer bin phpcs install
composer bin phpmd install
composer test
composer phpcs
composer phpmd
composer validate --strict
composer dump-autoload --optimize --strict-psr
find src -name '*.php' -exec php -l {} \;
composer audit
```

The tests exercise the converter against small standalone PHP fixtures. Full
database-metadata verification still requires the consuming Nextcloud
application and its existing runtime configuration.

GitHub Actions runs `composer test` on PHP 8.2 through 8.5 for pushes and pull requests.
The Tests workflow can also be started manually.
## Releases

Record changes under `## [Unreleased]` in [CHANGELOG.md](CHANGELOG.md). To
release, rename that section to `## [X.Y.Z] - YYYY-MM-DD` (or
`## [X.Y.Z-rcN] - YYYY-MM-DD` for a release candidate), commit, and push a tag
`vX.Y.Z` (or `vX.Y.Z-rcN`). The `Release`/`Prerelease` workflows run the tests,
verify via `scripts/check-changelog.sh` that the matching section exists and
that `CHANGELOG.md` changed since the previous tag, and publish a GitHub
(pre)release using that section as release notes.

After the changelog commit is on the release branch, `scripts/tag-release.sh
[remote]` reads the top-most release version from `CHANGELOG.md` at `HEAD`.
If its `vX.Y.Z[-rcN]` tag does not exist yet, the script asks for
confirmation (`y`/`yes`), creates an annotated tag on `HEAD`, checks it with
`scripts/check-changelog.sh`, and pushes it (default remote: `origin`).
