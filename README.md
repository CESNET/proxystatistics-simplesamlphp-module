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
4. Configure IdPAttribute filter from Perun module to get sourceIdPName from IdP metadata:  
```
    XX => array(
            'class' => 'perun:IdPAttribute',
            'attrMap' => array(
                    'name:en' => 'sourceIdPName',
            ),
    ),
    #where XX is priority(for example 30; Must not be used for other modules)
```
5. Configure proxystatistic filter
```
    XX => array(
            'class' => 'proxystatistics:statistics',
            'config' => array (),
    ),                
    #where XX is priority(for example 50; Must not be used for other modules)
```
6. Add to `config.php`:
```
'instance_name' => 'Instance name',
```
