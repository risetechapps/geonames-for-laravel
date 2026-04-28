# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-04-28

### Added
- `GeonamesInstall` Artisan command for complete package setup with migration generation
- `GeonamesInstallData` Artisan command to download geographic data on-demand from GitHub
- `GeonamesCacheWarm` Artisan command for pre-loading cache
- `GeonamesCacheClear` Artisan command for clearing cache
- `GeonamesBenchmark` Artisan command for performance testing
- `GeonamesValidator` class for validating countries, states and cities without instantiation
- `HasGeonames` trait for Laravel models with location support
- `Region` class with `countries()` relationship method
- Fuzzy search (`search()`) in `States` and `Cities` classes
- Pagination support (`paginate()`) in `States` and `Cities` classes
- Phone, zipcode and cellphone formatters with validation in `Country` class
- `ArrayAccess` and `JsonSerializable` implementations in `Country`, `State` and `City`
- `countries()` method to `Geonames` class
- `exists()` method to `Country` class for consistency with `State`
- `findByName()` method to `Regions` class
- `getNameLocalized()` method to `Country` class
- `getAvailableTranslations()` method to `Country` class
- `getDocuments()` and `getDocument()` methods to `Country` class
- `getTimezones()` method to `Country` class
- `getCoordinates()` method to `Country` class
- `getCurrencyName()`, `getTld()`, `getSubregion()`, `getNationality()` methods to `Country`
- `getEmojiU()` method to `Country` class
- `getCountryName()`, `getCountryIso2()`, `getCountryIso3()`, `getCountryNative()` to `State`
- `getId()` method to `State` class
- Complete configuration file with cache, pagination and data source settings

### Changed
- **BREAKING**: Data files moved from package (`resources/json/`) to application (`resources/geonames/json/`)
- **BREAKING**: Data files are no longer bundled - must be downloaded via `geonames:install-data` command
- Updated CI/CD workflow for PHP 8.3/8.4 and Laravel 12.x compatibility
- Changed data loading paths from `__DIR__` to `resource_path()` for proper application storage
- `Regions::find()` now returns `Region` object instead of array
- `GeonamesServiceProvider` now registers singleton and publishes config
- Updated `composer.json` with PSR-4 autoload for `Console` and `Traits` namespaces
- Improved error messages with instructions to run install commands

### Fixed
- Fixed `States` constructor removing invalid `return $this->all()` statement
- Added JSON validation with `RuntimeException` on parse errors in all data loaders
- Fixed memory issues by disabling Telescope recording during heavy commands
- Added `ini_set('memory_limit', '512M')` and garbage collection to cache-warm and install-data commands
- Fixed case sensitivity inconsistencies in search methods

### Removed
- Removed bundled `resources/json/` folder (~5500 files, ~100MB)
- Removed `__debugInfo()` methods from all classes
- Removed old CI/CD configuration for PHP 7.4/8.0 and Laravel 8.x

### Documentation
- Complete README rewrite with repository pattern explanation
- Added comprehensive API documentation for all public methods
- Added installation and configuration guide
- Added `docs/has-geonames-examples.md` with trait usage examples
- Added `.gitignore` files to prevent data files from being versioned

## [1.1.0] - 2026-02-04

- Corrigido carregamento de cidades

## [1.0.0] - 2025-12-24
### Added
- Lançamento inicial (Primeira versão estável).
