## [8.1.1](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v8.1.0...v8.1.1) (2022-09-01)


### Bug Fixes

* 🐛 Fix parameters ordering and duplicate insert ([ceef3ee](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/ceef3ee26903a8febe9488961b465afcc274b5b3))

# [8.1.0](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v8.0.0...v8.1.0) (2022-08-12)


### Features

* 🎸 Make methods for reading DB public ([957c87e](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/957c87e724d561e432dfb90dbc4c18d784701762))

# [8.0.0](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v7.1.1...v8.0.0) (2022-07-22)


### Features

* 🎸 API write ([44e507a](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/44e507aef480a9a029243fe92d61183cbc6b4776))


### BREAKING CHANGES

* Requires at least PHP v7.3 or higher (due to usage of
JSON_THROW_ON_ERROR constant)

## [7.1.1](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v7.1.0...v7.1.1) (2022-07-22)


### Bug Fixes

* 🐛 Fix ECS configuration (after update) ([8aa0c16](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/8aa0c1633033ccf2a2b36c26edbd775b74a58fec))
* 🐛 Fix possible exceptions and refactor code ([c5b5b41](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/c5b5b41f1c2cdcda06a0460f59c84e4187cb2fe1))

# [7.1.0](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v7.0.2...v7.1.0) (2022-07-22)


### Features

* 🎸 Customizable IdPEnityID location (from attribute) ([7d5d85e](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/7d5d85ee21507051dd7906f34469c05b172ffe18))

## [7.0.2](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v7.0.1...v7.0.2) (2022-04-04)


### Bug Fixes

* **deps:** minimum SSP version is 1.19.2 ([bdfead6](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/bdfead63948b3fb28799e952e52becc55589db19))

## [7.0.1](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v7.0.0...v7.0.1) (2022-02-07)


### Bug Fixes

* Fix migration script for MySQL ([8e215b8](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/8e215b85875d9720e8062d40f58aeb2754388b43))

# [7.0.0](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v6.0.0...v7.0.0) (2022-01-05)


### chore

* add missing composer dependencies ([2c9c3b9](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/2c9c3b9f920429d48b6ae15f39bcaf555fb80b90))


### BREAKING CHANGES

* SSP 1.19 is required

# [6.0.0](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v5.0.0...v6.0.0) (2021-11-22)


### Features

* adapt for PostgreSQL ([d778a96](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/d778a96c2f9b5d1a15b349b0effb4d44fea482d7))


### BREAKING CHANGES

* renamed columns idpId and spId, see migrations scripts

# [5.0.0](https://github.com/CESNET/proxystatistics-simplesamlphp-module/compare/v4.3.0...v5.0.0) (2021-11-18)


### chore

* **deps:** update Chart.js to 3.5.1 ([bfabef2](https://github.com/CESNET/proxystatistics-simplesamlphp-module/commit/bfabef2e28669292aa7d662f91bb58a6aadc092f))


### BREAKING CHANGES

* **deps:** legend items of pie chart do not link to detail pages anymore

# Change Log
All notable changes to this project will be documented in this file.

## [v4.3.0]
#### Added
- Add statistics view for 3 months

#### Fixed
- Change aggregation to deal with extra columns
- Use absolute URL instead of relative URLs on some places
- Correct dependencies

## [v4.2.2]
#### Fixed
- Allow to get SP name from UIInfo>DisplayName if isset

## [v4.2.1]
#### Fixed
- Fixed bad check for MULTI_IDP mode

## [v4.2.0]
#### Added
- Added MULTI_IDP mode

## [v4.1.0]
#### Removed
- Removed logging info about login in Statistics Process filter
    * For storing this log please use filter in module [Perun].
    
[Perun]: "https://github.com/CESNET/perun-simplesamlphp-module/blob/master/lib/Auth/Process/LoginInfo.php"

## [v4.0.0]
#### Added
- aggregated statistics (logins and unique users)

#### Changed
- new database tables
- config options for table names replaced with one (optional) option `tableNames`
- option 'config' for auth proc filter made optional
- auth proc filter renamed to Statistics (PascalCase)
- major refactoring

#### Removed
- `detailedDays` config option
- compatibility for deprecated database config options
- duplicate code

## [v3.2.1]
#### Fixed
- Fixed the bug in using double '$'

## [v3.2.0]
#### Added
- Added possibility to show statistics only after authentication

#### Changed
- Remove unnecessary is_null()
- Use SimpleSAML\Database

#### Fixed
- Log info message about successful authentication only after successful authentication to SP
- Correct log message in insertLogin()
- Update README.md
    - describe setup for modes PROXY/SP/IDP
    - change array notation from `array()` to `[]`
- Read spName from $request only if present
- Remove unused indexes
- Optimize left outer join
- Don't double queries w/o days
- Fixed the table header in detailed statistics for SP

## [v3.1.0]
#### Added
- Added configuration file for ESLint
- Module now supports running statistics as IDP/SP
- Store detailed statistics(include some user identifier) for several days 

#### Changed
- Using of short array syntax (from array() to [])
- Specify engine and default charset in tables.sql
- Removed unused include from 'templates/spDetail-tpl.php'
- Deleted useless code
- Deleted 'head' and 'body' tag in tab templates
- Use 'filter_input' to GET and VALIDATE value send as GET/POST param
- Eliminate inline javascript
    - All JS code was moved to 'index.js'
- Using 'fetch_all' instead of 'fetch_asoc' to get data from DB
- Set default values for some option in 'DatabaseConnector.php'
- Remove duplicate code from 'DatabaseConnector.php'
- Move duplicate code for timeRange to separate file
- Use import instead of unnecessary qualifier

#### Fixed
- Fixed the syntax of CHANGELOG
- Fixed SQL injection vulnerability
- Fixed list of required packages

## [v3.0.0]
#### Added
- Added file phpcs.xml

#### Fixed
- Fixed the problem with generating error, when some of attributes 'eduPersonUniqueId', 'sourceIdPEppn', 'sourceIdPEntityId' is null 

#### Changed
- Changed code style to PSR-2
- Module uses namespaces

## [v2.1.0]
#### Added
- Every successfully log in is logged with notice level 

## [v2.0.0]
#### Added
- Added details with statistics for individually SPs and IdPs
- Added script for migrate data to new version of database structure

## [v1.5.0]
#### Added
- Added legends to charts
- Instance name in header is taken from config file

#### Fixed
- set default value of lastDays and tab in index.php: no error logs when user open statistics for the first time

## [v1.4.1]
#### Fixed
- Statistics will be now full screen
- Fixed bad checks before insert translation to db

## [v1.4.0]
#### Added
- Possibility to change the time range of displayed data

#### Changed
- DB commands work with apostrophes in IdP/SP names
- New visual form of the site
- Draw tables without month

#### Fixed
- Draws tables data by selected time range

#### Removed
- Removed unused functions

## [v1.3.0]
#### Added
- Added mapping tables for mapping identifier to name

#### Changed
- Storing entityIds instead of SpName/IdPName. 

#### Fixed
- Used only tabs for indentations

## [v1.2.1]
#### Fixed
- Fixed the problem with getting utf8 chars from database

## [v1.2.0]
#### Added
- Classes SimpleSAML_Logger and SimpleSAML_Module renamed to SimpleSAML\Logger and SimpleSAML\Module
- Dictionary
- Czech translation

#### Changed
- Database commands use prepared statements
- Saving SourceIdPName instead of EntityId

## [v1.1.0]
#### Added
- Added average and maximal count of logins per day into summary table

#### Changed
- Fixed overqualified element in statisticsproxy.css

## [v1.0.0]
#### Added
- Changelog

[Unreleased]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/master
[v4.3.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v4.3.0
[v4.2.2]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v4.2.2
[v4.2.1]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v4.2.1
[v4.2.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v4.2.0
[v4.1.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v4.1.0
[v4.0.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v4.0.0
[v3.2.1]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v3.2.1
[v3.2.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v3.2.0
[v3.1.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v3.1.0
[v3.0.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v3.0.0
[v2.1.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v2.1.0
[v2.0.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v2.0.0
[v1.5.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.5.0
[v1.4.1]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.4.1
[v1.4.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.4.0
[v1.3.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.3.0
[v1.2.1]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.2.1
[v1.2.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.2.0
[v1.1.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.1.0
[v1.0.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.0.0
