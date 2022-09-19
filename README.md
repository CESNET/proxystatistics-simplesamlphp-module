# proxystatistics-simplesamlphp-module
[![Latest Stable Version](https://poser.pugx.org/cesnet/simplesamlphp-module-proxystatistics/v/stable)](https://packagist.org/packages/cesnet/simplesamlphp-module-proxystatistics)
[![Total Downloads](https://poser.pugx.org/cesnet/simplesamlphp-module-proxystatistics/downloads)](https://packagist.org/packages/cesnet/simplesamlphp-module-proxystatistics)
[![CI](https://github.com/CESNET/proxystatistics-simplesamlphp-module/actions/workflows/build_and_check.yml/badge.svg)](https://github.com/CESNET/proxystatistics-simplesamlphp-module/actions/workflows/build_and_check.yml)
[![License](https://poser.pugx.org/cesnet/simplesamlphp-module-proxystatistics/license)](https://packagist.org/packages/cesnet/simplesamlphp-module-proxystatistics)

**REPOSITORY HAS BEEN MOVED TO: https://gitlab.ics.muni.cz/perun-proxy-aai/simplesamlphp/simplesamlphp-module-proxystatistics**

Module for simpleSAMLphp which shows Proxy IdP/SP statistics

## Contribution

This repository uses [Conventional Commits](https://www.npmjs.com/package/@commitlint/config-conventional).

Any change that significantly changes behavior in a backward-incompatible way or requires a configuration change must be marked as BREAKING CHANGE.

### Available scopes:
* design
* Auth Process filters:
    * statistics
* ...


## Instalation
Once you have installed SimpleSAMLphp, installing this module is very simple. First of all, you will need to download Composer if you haven't already. After installing Composer, just execute the following command in the root of your SimpleSAMLphp installation:

`php composer.phar require cesnet/simplesamlphp-module-proxystatistics`


## Configuration
1. Install MySQL Database and create database for statistics and user. 
2. For this database run script to create tables. Script is available in config-templates/tables.sql.
3. Copy config-templates/module_proxystatistics.php to your config folder and fill it.
4. Configure, according to mode

* PROXY - collects data about number of logins from each identity provider and accessed services; for PROXY mode, configure IdPAttribute filter from Perun module to get sourceIdPName from IdP metadata:
```
    50 => [
        'class' => 'perun:IdPAttribute',
        'attrMap' => [
            'name:en' => 'sourceIdPName',
        ],
    ],
    // where 50 is priority (for example, must not be used for other modules)
```
* IDP - collects data about accessed services through given identity provider; for IDP mode, configure entity ID and name in `module_proxystatistics.php`
```
    'IDP' => [
        'id' => '',
        'name' => '',
    ],
```
* SP - collects data about identity providers used for access to given service; for SP mode, configure entity ID and name in `module_proxystatistics.php`
```
    'SP' => [
        'id' => '',
        'name' => '',
    ],
```
* MULTI_IDP - similar to IDP mode, stores more identity providers in one database; for MULTI_IDP mode, configure entity ID and name in each `module_proxystatistics.php` we want to get statistics from
```
    'IDP' => [
        'id' => '',
        'name' => '',
    ],
```
5. Configure proxystatistics filter
```
    50 => [
        'class' => 'proxystatistics:Statistics',
    ],
    // where 50 is priority (for example, must not be used for other modules)
```
6. Add to `config.php`:
```
'instance_name' => 'Instance name',
```

### Writing via API
#### Configuration
Add the following (and adjust the credentials) to enable writing via the API (example request following). Methods supported are `POST,PUT`.
```
  'apiWriteEnabled' => true,
  'apiWriteUsername' => 'api_writer',
  'apiWritePasswordHash' => password_hash('ap1Wr1T3rP@S$'),
```
#### Example request
```
curl --request POST \
  --url https://proxy.com/proxy/module.php/proxystatistics/writeLoginApi.php \
  --header 'Authorization: Basic encodedCredentials' \
  --header 'Content-Type: application/json' \
  --data '{
	"userId": "user@somewhere.edu",
	"serviceIdentifier": "https://service.com/shibboleth",
	"serviceName": "TEST_SERVICE",
	"idpIdentifier": "https://idp.org/simplesamlphp",
	"idpName": "TEST_IDP"
}'
```
