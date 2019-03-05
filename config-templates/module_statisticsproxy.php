<?php
/**
 * This is example configuration of SimpleSAMLphp Perun interface and additional features.
 * Copy this file to default config directory and edit the properties.
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

$config = array(

    /*
     * Fill the serverName
     */
    'serverName' => 'localhost',

    /*
     * If you want to use the default port, please comment option 'port'
     */
    'port' => 3306,

    /*
     * Fill the user name
     */
    'userName' => 'stats',

    /*
     * Fill the password
     */
    'password' => 'stats',

    /*
     * Fill the database name
     */
    'databaseName' => 'STATS',

    /*
     * Fill the table name for statistics
     */
    'statisticsTableName' => 'statisticsTableName',

    /*
     * Fill the table name for identityProvidersMap
     */
    'identityProvidersMapTableName' => 'identityProvidersMap',

    /*
     * Fill the table name for serviceProviders
     */
    'serviceProvidersMapTableName' => 'serviceProvidersMap',

    /*
     * Fill true, if you want to use encryption, false if not.
     */
    'encryption' => true / false,

    /*
     * The path name to the certificate authority file.
     *
     * If you use encryption, you must fill this option.
     */
    'ssl_ca' => '/example/ca.pem',

    /*
     * The path name to the certificate file.
     *
     * If you use encryption, you must fill this option.
     */
    'ssl_cert_path' => '/example/cert.pem',

    /*
     * The path name to the key file.
     *
     * If you use encryption, you must fill this option.
     */
    'ssl_key_path' => '/example/key.pem',

    /*
     * The pathname to a directory that contains trusted SSL CA certificates in PEM format.
     *
     * If you use encryption, you must fill this option.
     */
    'ssl_ca_path' => '/etc/ssl',

);
