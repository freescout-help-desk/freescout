# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.0.2 - 2020-07-30

### Changed

- [#71](https://github.com/Bacon/BaconQrCode/issues/71) Upgrade phpunit.
- [#71](https://github.com/Bacon/BaconQrCode/issues/71) Allow tests in vendor bundles for Debian packaging.
- [#71](https://github.com/Bacon/BaconQrCode/issues/71) Update TravisCI config file.

## 2.0.1 - 2020-07-14

### Fixed

- [#69](https://github.com/Bacon/BaconQrCode/pull/69) SimpleCircleEye Class not working properly.

## 2.0.0 - 2018-04-25

### Added

- [#25](https://github.com/Bacon/BaconQrCode/pull/25) allows for setting a more compact text output

- CHANGELOG.md added (how meta)

- Allows more complex shapes for modules

- Allows setting a gradient for the foreground

- Allows transparent backgrounds and alpha channel on all colors

### Changed

- Minimum PHP version changed to 7.1

- Imagick renderer now allows setting different output formats

- New optimized SVG renderer

### Deprecated

- Nothing.

### Removed

- Legacy ZF module support removed

### Fixed

- Non-release files are excluded from composer packages
