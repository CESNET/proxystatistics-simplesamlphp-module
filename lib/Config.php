<?php

declare(strict_types=1);

namespace SimpleSAML\Module\proxystatistics;

use SimpleSAML\Configuration;

class Config
{
    public const CONFIG_FILE_NAME = 'module_proxystatistics.php';

    public const MODE_IDP = 'IDP';

    public const MODE_SP = 'SP';

    public const MODE_MULTI_IDP = 'MULTI_IDP';

    public const MODE_PROXY = 'PROXY';

    public const KNOWN_MODES = [self::MODE_IDP, self::MODE_SP, self::MODE_MULTI_IDP, self::MODE_PROXY];

    public const SIDES = [self::MODE_IDP, self::MODE_SP];

    private const STORE = 'store';

    private const TABLES = 'tables';

    private const MODE = 'mode';

    private const USER_ID_ATTRIBUTE = 'userIdAttribute';

    private const SOURCE_IDP_ENTITY_ID_ATTRIBUTE = 'sourceIdpEntityIdAttribute';

    private const REQUIRE_AUTH_SOURCE = 'requireAuth.source';

    private const KEEP_PER_USER = 'keepPerUser';

    private $config;

    private $store;

    private $tables;

    private $mode;

    private static $instance;

    private function __construct()
    {
        $this->config = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->store = $this->config->getConfigItem(self::STORE, null);
        $this->tables = $this->config->getArray(self::TABLES, []);
        $this->mode = $this->config->getValueValidate(self::MODE, self::KNOWN_MODES, self::MODE_PROXY);
    }

    private function __clone()
    {
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function getStore()
    {
        return $this->store;
    }

    public function getIdAttribute()
    {
        return $this->config->getString(self::USER_ID_ATTRIBUTE, 'uid');
    }

    public function getSourceIdpEntityIdAttribute()
    {
        return $this->config->getString(self::SOURCE_IDP_ENTITY_ID_ATTRIBUTE, '');
    }

    public function getSideInfo($side)
    {
        assert(in_array($side, self::SIDES, true));

        return array_merge([
            'name' => '',
            'id' => '',
        ], $this->config->getArray($side, []));
    }

    public function getRequiredAuthSource()
    {
        return $this->config->getString(self::REQUIRE_AUTH_SOURCE, '');
    }

    public function getKeepPerUser()
    {
        return $this->config->getIntegerRange(self::KEEP_PER_USER, 31, 1827, 31);
    }
}
