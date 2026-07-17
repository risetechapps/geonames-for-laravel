# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.
O formato é baseado em [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), e este projeto segue o [Versionamento Semântico](https://semver.org/lang/pt-BR/) (SemVer).

## [3.0.0] - 2026-07-17
- Corrigido parâmetros e funções obsoletas em php 8.4
- Upgrade para php 8.4

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
- 
## [1.1.0] - 2026-02-04
- Corrigido carregamento de cidades


## [1.0.0] - 2025-12-24
### Added
- Lançamento inicial (Primeira versão estável).
