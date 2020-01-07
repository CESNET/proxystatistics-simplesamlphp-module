# proxystatistics-simplesamlphp-module
[![Latest Stable Version](https://poser.pugx.org/cesnet/simplesamlphp-module-proxystatistics/v/stable)](https://packagist.org/packages/cesnet/simplesamlphp-module-proxystatistics)
[![Total Downloads](https://poser.pugx.org/cesnet/simplesamlphp-module-proxystatistics/downloads)](https://packagist.org/packages/cesnet/simplesamlphp-module-proxystatistics)
[![CodeFactor](https://www.codefactor.io/repository/github/cesnet/proxystatistics-simplesamlphp-module/badge)](https://www.codefactor.io/repository/github/cesnet/proxystatistics-simplesamlphp-module)
[![License](https://poser.pugx.org/cesnet/simplesamlphp-module-proxystatistics/license)](https://packagist.org/packages/cesnet/simplesamlphp-module-proxystatistics)

Module for simpleSAMLphp which shows Proxy IdP/SP statistics

## Instalation
Once you have installed SimpleSAMLphp, installing this module is very simple. First of all, you will need to download Composer if you haven't already. After installing Composer, just execute the following command in the root of your SimpleSAMLphp installation:

`php composer.phar require cesnet/simplesamlphp-module-proxystatistics`


## Configuration
1. Install MySQL Database and create database for statistics and user. 
2. For this database run script to create tables. Script is available in config-templates/tables.sql.
3. Copy config-templates/module_statisticsproxy.php to your folder vith config and fill it.
4. Configure, according to mode
* for mode PROXY, configure IdPAttribute filter from Perun module to get sourceIdPName from IdP metadata:  
```
    50 => [
            'class' => 'perun:IdPAttribute',
            'attrMap' => [
                    'name:en' => 'sourceIdPName',
            ],
    ],
    // where 50 is priority (for example, must not be used for other modules)
```
* for mode IDP, configure `idpEntityId` and `idpName` in `module_statisticsproxy.php`
```
    /*
     * EntityId of IdP
     * REQUIRED FOR IDP MODE
     */
    'idpEntityId' => '',
    /*
     * Name of IdP
     * REQUIRED FOR IDP MODE
     */
    'idpName' => '',
```
* for mode SP, configure `spEntityId` and `spName` in `module_statisticsproxy.php`
```
    /*
     * EntityId of SP
     * REQUIRED FOR SP MODE
     */
    'spEntityId' => '',
    /*
     * Name of SP
     * REQUIRED FOR SP MODE
     */
    'spName' => '',
```
5. Configure proxystatistic filter
```
    50 => array(
            'class' => 'proxystatistics:Statistics',
            'config' => [],
    ),                
    // where 50 is priority (for example, must not be used for other modules)
```
6. Add to `config.php`:
```
'instance_name' => 'Instance name',
```
