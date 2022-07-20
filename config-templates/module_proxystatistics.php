<?php

declare(strict_types=1);

/**
 * This is an example configuration of SimpleSAMLphp Perun interface and additional features. Copy this file to default
 * config directory and edit the properties.
 */
$config = [
    /*
     * Choose one of the following modes: PROXY, IDP, SP, MULTI_IDP
     * PROXY - collects data about number of logins from each identity provider and accessed services
     * IDP - collects data about accessed services through given identity provider
     * SP - collects data about identity providers used for access to given service
     * MULTI_IDP - similar to IDP mode, stores more identity providers in one database
     */
    'mode' => 'PROXY',

    /*
     * EntityId and name of IdP
     * REQUIRED FOR IDP AND MULTI_IDP MODE
     */
    //'IDP' => [
    //    'id' => '',
    //    'name' => '',
    //],

    /*
     * EntityId and name of SP
     * REQUIRED FOR SP MODE
     */
    //'SP' => [
    //    'id' => '',
    //    'name' => '',
    //],

    /*
     * Config for SimpleSAML\Database.
     * If not set, the global config is used.
     * @see SimpleSAML\Database
     */
    'store' => [
        'database.dsn' => 'mysql:host=localhost;port=3306;dbname=STATS;charset=utf8',
        'database.username' => 'stats',
        'database.password' => 'stats',

        /*
         * Configuration for SSL If you want to use SSL, fill these values and uncomment the block of code
         */
        //'database.driver_options' => [
        //    PDO::MYSQL_ATTR_SSL_KEY => '', // Path for the ssl key file
        //    PDO::MYSQL_ATTR_SSL_CERT => '', // Path for the ssl cert file
        //    PDO::MYSQL_ATTR_SSL_CA => '', // Path for the ssl ca file
        //    PDO::MYSQL_ATTR_SSL_CAPATH => '', // Path for the ssl ca dir
        //],
    ],

    /*
     * Which attribute should be used as user ID.
     *
     * @default uid
     */
    //'userIdAttribute' => 'uid',

    /*
     * Which attribute should be used for IdP Entity ID
     * if left empty, it will be extracted from the request object.
     */
    //'sourceIdpEntityIdAttribute' => 'sourceIdpEntityID',

    /*
     * Database table names. Default is to keep the name (as in `tables.sql`)
     */
    'tableNames' => [
        //'statistics_sums' => 'statistics_sums',
        //'statistics_per_user' => 'statistics_per_user',
        //'statistics_idp' => 'statistics_idp',
        //'statistics_sp' => 'statistics_sp',
    ],

    /*
     * Authentication source name if authentication should be required. Defaults to empty string.
     */
    //'requireAuth.source' => 'default-sp',

    /*
     * For how many days should the detailed statistics be kept. Minimum is 31.
     */
    //'keepPerUser' => 62,
];
