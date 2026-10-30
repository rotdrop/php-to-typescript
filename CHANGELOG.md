# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Release candidates use versions of the form `X.Y.Z-rcN`. Pushing a tag
`vX.Y.Z` or `vX.Y.Z-rcN` publishes a GitHub release (respectively prerelease)
whose notes are taken from the matching `## [X.Y.Z]` / `## [X.Y.Z-rcN]`
section below; the release workflow fails if that section is missing or if
this file was not changed since the previous tag.

## [Unreleased]

## [2.0.0] - 2026-10-30

### Added

- Optional `entityManagerFactory` closure for `PhpToTypeScript` and
  `GenerateEntityMetadata`, supplying the application's entity manager.

### Changed

- **Breaking:** the CAFEVDB-specific entity manager bootstrap (`CAFEVDB_USER`,
  `.clipassword`, encryption service) has been removed. Applications using
  `DatabaseEntityCollector` with `--as-modules` must pass an
  `entityManagerFactory` closure to `PhpToTypeScript`.

## [1.0.1] - 2026-10-30

### Added

- PHPMD and PHP_CodeSniffer development tools with the CAFEVDB patches and rulesets (#6, #7).

### Fixed

- `DTOCollector` checks only the installed `AbstractDTO` variant (unscoped or
  php-scoper-wrapped) instead of autoloading several candidates, and no longer
  substitutes the app namespace (#8).

## [1.0.0] - 2026-10-30

### Added

- Extract the PHP-to-TypeScript converter from
  `rotdrop/nextcloud-app-build-system` into a standalone Composer library (#1).
- Basic converter tests and a PHP 8.2–8.5 CI workflow (#2).
- Support for php-scoper-wrapped toolkit namespaces in DTO collection (#3).
- Configurable folder for the entity-metadata forwarder (#3).
- Changelog, pull request template and tag-driven release workflows.
- `scripts/tag-release.sh` to create and push the tag for the top-most
  changelog release after confirmation.
