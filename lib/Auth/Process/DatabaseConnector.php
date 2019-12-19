<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use SimpleSAML\Configuration;
use SimpleSAML\Database;
use SimpleSAML\Logger;
use PDO;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

class DatabaseConnector
{
    private $statisticsTableName;
    private $detailedStatisticsTableName;
    private $identityProvidersMapTableName;
    private $serviceProvidersMapTableName;
    private $mode;
    private $idpEntityId;
    private $idpName;
    private $spEntityId;
    private $spName;
    private $detailedDays;
    private $userIdAttribute;
    private $conn = null;

    const CONFIG_FILE_NAME = 'module_statisticsproxy.php';
    const STATS_TABLE_NAME = 'statisticsTableName';
    const DETAILED_STATS_TABLE_NAME = 'detailedStatisticsTableName';
    const IDP_MAP_TABLE_NAME = 'identityProvidersMapTableName';
    const SP_MAP_TABLE_NAME = 'serviceProvidersMapTableName';
    const STORE = 'store';
    const MODE = 'mode';
    const IDP_ENTITY_ID = 'idpEntityId';
    const IDP_NAME = 'idpName';
    const SP_ENTITY_ID = 'spEntityId';
    const SP_NAME = 'spName';
    const DETAILED_DAYS = 'detailedDays';
    const USER_ID_ATTRIBUTE = 'userIdAttribute';

    public function __construct()
    {
        $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->storeConfig = $conf->getArray(self::STORE, null);

        $this->storeConfig = Configuration::loadFromArray($this->storeConfig);

        $this->statisticsTableName = $conf->getString(self::STATS_TABLE_NAME);
        $this->detailedStatisticsTableName = $conf->getString(self::DETAILED_STATS_TABLE_NAME, 'statistics_detail');
        $this->identityProvidersMapTableName = $conf->getString(self::IDP_MAP_TABLE_NAME);
        $this->serviceProvidersMapTableName = $conf->getString(self::SP_MAP_TABLE_NAME);
        $this->mode = $conf->getString(self::MODE, 'PROXY');
        $this->idpEntityId = $conf->getString(self::IDP_ENTITY_ID, '');
        $this->idpName = $conf->getString(self::IDP_NAME, '');
        $this->spEntityId = $conf->getString(self::SP_ENTITY_ID, '');
        $this->spName = $conf->getString(self::SP_NAME, '');
        $this->detailedDays = $conf->getInteger(self::DETAILED_DAYS, 0);
        $this->userIdAttribute = $conf->getString(self::USER_ID_ATTRIBUTE, 'uid');
    }

    public function getConnection()
    {
        return Database::getInstance($this->storeConfig);
    }

    public function getStatisticsTableName()
    {
        return $this->statisticsTableName;
    }

    public function getDetailedStatisticsTableName()
    {
        return $this->detailedStatisticsTableName;
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

    public function getDetailedDays()
    {
        return $this->detailedDays;
    }

    public function getUserIdAttribute()
    {
        return $this->userIdAttribute;
    }
}
