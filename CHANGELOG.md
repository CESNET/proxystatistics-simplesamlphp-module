# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]
[Changed]
- 100% of screen width is used now

## [v1.4.0]
[Added]
- Possibility to change the time range of displayed data

[Changed]
- DB commands work with apostrophes in IdP/SP names
- New visual form of the site
- Draw tables without month

[Fixed]
- Draws tables data by selected time range

[Removed]
- Removed unused functions

## [v1.3.0]
[Added]
- Added mapping tables for mapping identifier to name

[Changed]
- Storing entityIds instead of SpName/IdPName. 

[Fixed]
- Used only tabs for indentations

## [v1.2.1]
[Fixed]
- Fixed the problem with getting utf8 chars from database

## [v1.2.0]
[Added]
- Classes SimpleSAML_Logger and SimpleSAML_Module renamed to SimpleSAML\Logger and SimpleSAML\Module
- Dictionary
- Czech translation

[Changed]
- Database commands use prepared statements
- Saving SourceIdPName instead of EntityId

## [v1.1.0]
[Added]
- Added average and maximal count of logins per day into summary table

[Changed]
- Fixed overqualified element in statisticsproxy.css

## [v1.0.0]
[Added]
- Changelog

[Unreleased]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/master
[v1.4.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.4.0
[v1.3.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.3.0
[v1.2.1]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.2.1
[v1.2.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.2.0
[v1.1.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.1.0
[v1.0.0]: https://github.com/CESNET/proxystatistics-simplesamlphp-module/tree/v1.0.0
