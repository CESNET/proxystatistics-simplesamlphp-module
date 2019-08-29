<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

class DatabaseConnector
{
    private $serverName;
    private $port;
    private $username;
    private $password;
    private $databaseName;
    private $statisticsTableName;
    private $identityProvidersMapTableName;
    private $serviceProvidersMapTableName;
    private $encryption;
    private $sslCA;
    private $sslCert;
    private $sslKey;
    private $sslCAPath;
    private $mode;
    private $idpEntityId;
    private $idpName;
    private $spEntityId;
    private $spName;

    const CONFIG_FILE_NAME = 'module_statisticsproxy.php';
    const SERVER = 'serverName';
    const PORT = 'port';
    const USER = 'userName';
    const PASSWORD = 'password';
    const DATABASE = 'databaseName';
    const STATS_TABLE_NAME = 'statisticsTableName';
    const IDP_MAP_TABLE_NAME = 'identityProvidersMapTableName';
    const SP_MAP_TABLE_NAME = 'serviceProvidersMapTableName';
    const ENCRYPTION = 'encryption';
    const SSL_CA = 'ssl_ca';
    const SSL_CERT = 'ssl_cert_path';
    const SSL_KEY = 'ssl_key_path';
    const SSL_CA_PATH = 'ssl_ca_path';
    const MODE = 'mode';
    const IDP_ENTITY_ID = 'idpEntityId';
    const IDP_NAME = 'idpName';
    const SP_ENTITY_ID = 'spEntityId';
    const SP_NAME = 'spName';

    public function __construct()
    {
        $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->serverName = $conf->getString(self::SERVER);
        $this->port = $conf->getInteger(self::PORT, 3306);
        $this->username = $conf->getString(self::USER);
        $this->password = $conf->getString(self::PASSWORD);
        $this->databaseName = $conf->getString(self::DATABASE);
        $this->statisticsTableName = $conf->getString(self::STATS_TABLE_NAME);
        $this->identityProvidersMapTableName = $conf->getString(self::IDP_MAP_TABLE_NAME);
        $this->serviceProvidersMapTableName = $conf->getString(self::SP_MAP_TABLE_NAME);
        $this->encryption = $conf->getBoolean(self::ENCRYPTION, false);
        $this->sslCA = $conf->getString(self::SSL_CA, '');
        $this->sslCert = $conf->getString(self::SSL_CERT, '');
        $this->sslKey = $conf->getString(self::SSL_KEY, '');
        $this->sslCAPath = $conf->getString(self::SSL_CA_PATH, '');
        $this->mode = $conf->getString(self::MODE, 'PROXY');
        $this->idpEntityId = $conf->getString(self::IDP_ENTITY_ID, '');
        $this->idpName = $conf->getString(self::IDP_NAME, '');
        $this->spEntityId = $conf->getString(self::SP_ENTITY_ID, '');
        $this->spName = $conf->getString(self::SP_NAME, '');
    }

    public function getConnection()
    {
        $conn = mysqli_init();
        if ($this->encryption === true) {
            Logger::debug('Getting connection with encryption.');
            mysqli_ssl_set(
                $conn,
                $this->sslKey,
                $this->sslCert,
                $this->sslCA,
                $this->sslCAPath,
                null
            );
        }
        mysqli_real_connect(
            $conn,
            $this->serverName,
            $this->username,
            $this->password,
            $this->databaseName,
            $this->port
        );
        mysqli_set_charset($conn, 'utf8');
        return $conn;
    }

    public function getStatisticsTableName()
    {
        return $this->statisticsTableName;
    }

    public function getIdentityProvidersMapTableName()
    {
        return $this->identityProvidersMapTableName;
    }

    public function getServiceProvidersMapTableName()
    {
        return $this->serviceProvidersMapTableName;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getIdpEntityId()
    {
        return $this->idpEntityId;
    }

    public function getIdpName()
    {
        return $this->idpName;
    }

    public function getSpEntityId()
    {
        return $this->spEntityId;
    }

    public function getSpName()
    {
        return $this->spName;
    }
}
