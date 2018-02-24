# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
## [0.15.2] - 2018-02-25 
### Fixed
- DF-1303 Correct native date and time format

## [0.15.1] - 2018-01-25 
### Added
- DF-1275 Initial support for multi-column constraints

## [0.15.0] - 2017-12-28
### Added
- DF-1224 Added ability to set different default limits (max_records_returned) per service
- Added package discovery
### Changed
- DF-1150 Update copyright and support email
- Updated dependencies
- Separated resources from resource handlers

## [0.14.0] - 2017-11-03
### Changed
- Change getNativeDateTimeFormat to handle column schema to detect detailed datetime format
- Move preferred schema naming to service level
- Add subscription requirements to service provider

## [0.13.0] - 2017-09-18
### Added
- Add new support for HAS_ONE relationship
### Fixed
- DF-1160 Correct resource name usage for procedures and functions when pulling parameters
- Cleanup primary and unique key handling

## [0.12.0] - 2017-08-17
### Changed
- Reworked API doc usage and generation
- Set config-based cache prefix

## [0.11.0] - 2017-07-27
- Separating base schema from SQL schema
- Datetime settings handling

## [0.10.0] - 2017-06-05
### Changed
- Cleanup - removal of php-utils dependency

## [0.9.0] - 2017-04-21
### Changed
- Use new service config handling for database configuration

## [0.8.0] - 2017-03-03
- Major restructuring to upgrade to Laravel 5.4 and be more dynamically available
### Added
- DF-946 Handled 'table' return type for stored functions
### Changed
- Switching to using yajra/laravel-oci8 instead of our old fork

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

[Unreleased]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.15.2...HEAD
[0.15.2]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.15.1...0.15.2
[0.15.1]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.15.0...0.15.1
[0.15.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.14.0...0.15.0
[0.14.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.13.0...0.14.0
[0.13.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.12.0...0.13.0
[0.12.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.11.0...0.12.0
[0.11.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.10.0...0.11.0
[0.10.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.9.0...0.10.0
[0.9.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.8.0...0.9.0
[0.8.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.7.0...0.8.0
[0.7.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.6.0...0.7.0
[0.6.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.5.2...0.6.0
[0.5.2]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.5.1...0.5.2
[0.5.1]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.4.1...0.5.0
[0.4.1]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/dreamfactorysoftware/df-oracledb/compare/0.3.0...0.3.1
