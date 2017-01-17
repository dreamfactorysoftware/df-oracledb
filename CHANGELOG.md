# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
### Changed
### Fixed

## [0.7.0] - 2017-01-16
### Changed
- Adhere to refactored df-core, see df-database
- Cleanup schema management issues

## [0.6.0] - 2016-11-17
- Virtual relationships rework to support all relationship types
- DB base class changes to support field configuration across all database types

## [0.5.2] - 2016-10-28
### Changed
- DF-880 Support for procedures and functions declared in packages

## [0.5.1] - 2016-10-28
### Changed
- OCI8 does not support FETCH_NAMED, roll back to FETCH_ASSOC

## [0.5.0] - 2016-10-03
### Changed
- Update to latest df-core and df-sqldb changes

## [0.4.1] - 2016-09-21
### Fixed
- Fix configuration validation for TNS usage and database versus service_name.

## [0.4.0] - 2016-08-21
### Changed
- General cleanup from declaration changes in df-core for service doc and providers

## [0.3.1] - 2016-07-08
### Added
- DF-636 Adding ability using 'ids' parameter to return the schema of a stored procedure or function.
- DF-629 Final fixes and add support for ref_cursor in Oracle procedures.

### Fixed
- DF-807 Mistakenly changed case on DB connection causes field lookup issues.

## 0.3.0 - 2016-05-27
First official release working with the new [df-core](https://github.com/dreamfactorysoftware/df-core) library.

[Unreleased]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.7.0...HEAD
[0.7.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.6.0...0.7.0
[0.6.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.5.2...0.6.0
[0.5.2]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.5.1...0.5.2
[0.5.1]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.4.1...0.5.0
[0.4.1]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.3.0...0.3.1
