# Changelog

All notable changes to this repository are documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/). `ckchangelog --help` lists the grammar CI enforces.

## [Unreleased]

## [0.1.0] - 2026-11-26

### Added

- SQL functions `MEDIAN`, `PERCENTILE` and `STDDEV` for APIv4 and SearchKit.
- SQL functions `IN_PERIOD`, `SUM_IN_PERIOD` and `COUNT_IN_PERIOD` over CiviCRM's relative date periods.
- Each period gets its own column name in APIv4 and in the SearchKit editor, so two periods of the same fields can stand side by side.
- afreact's React SearchKit builder saves searches with abacus enabled: abacus declares
  its editor module covered there, since that builder names the period in the alias itself.

[Unreleased]: https://github.com/civico-de/abacus/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/civico-de/abacus/releases/tag/v0.1.0
