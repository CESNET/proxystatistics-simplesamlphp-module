<?php

declare(strict_types=1);

namespace SimpleSAML\Module\proxystatistics;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;

class Config
{
    public const CONFIG_FILE_NAME = 'module_proxystatistics.php';

    public const MODE_IDP = 'IDP';

    public const MODE_SP = 'SP';

    public const MODE_MULTI_IDP = 'MULTI_IDP';

    public const SIDES = [self::MODE_IDP, self::MODE_SP];

    public const MODE_PROXY = 'PROXY';

    private const KNOWN_MODES = ['PROXY', 'IDP', 'SP', 'MULTI_IDP'];

    private const STORE = 'store';

    private const MODE = 'mode';

    private const USER_ID_ATTRIBUTE = 'userIdAttribute';

    private const SOURCE_IDP_ENTITY_ID_ATTRIBUTE = 'sourceIdpEntityIdAttribute';

    private const REQUIRE_AUTH_SOURCE = 'requireAuth.source';

    private const KEEP_PER_USER = 'keepPerUser';

    private const API_WRITE_ENABLED = 'apiWriteEnabled';

    private const API_WRITE_USERNAME = 'apiWriteUsername';

    private const API_WRITE_PASSWORD_HASH = 'apiWritePasswordHash';

    private $config;

    private $store;

    private $mode;

    private $sourceIdpEntityIdAttribute;

    private $tables;

    private $keepPerUser;

    private $requiredAuthSource;

    private $idAttribute;

    private $apiWriteEnabled;

    private $apiWriteUsername;

    private $apiWritePasswordHash;

    private static $instance;

    private function __construct()
    {
        $this->config = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->store = $this->config->getConfigItem(self::STORE, null);
        $this->tables = $this->config->getArray('tables', []);
        $this->sourceIdpEntityIdAttribute = $this->config->getString(self::SOURCE_IDP_ENTITY_ID_ATTRIBUTE, '');
        $this->mode = $this->config->getValueValidate(self::MODE, self::KNOWN_MODES, self::MODE_PROXY);
        $this->keepPerUser = $this->config->getIntegerRange(self::KEEP_PER_USER, 31, 1827, 31);
        $this->requiredAuthSource = $this->config->getString(self::REQUIRE_AUTH_SOURCE, '');
        $this->idAttribute = $this->config->getString(self::USER_ID_ATTRIBUTE, 'uid');
        $this->apiWriteEnabled = $this->config->getBoolean(self::API_WRITE_ENABLED, false);
        if ($this->apiWriteEnabled) {
            $this->apiWriteUsername = $this->config->getString(self::API_WRITE_USERNAME);
            if (empty(trim($this->apiWriteUsername))) {
                throw new Exception('Username for API write cannot be empty');
            }
            $this->apiWritePasswordHash = $this->config->getString(self::API_WRITE_PASSWORD_HASH);
            if (empty(trim($this->apiWritePasswordHash))) {
                throw new Exception('Password for API write cannot be empty');
            }
        }
    }

    private function __clone()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
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
        return $this->idAttribute;
    }

    public function getSourceIdpEntityIdAttribute()
    {
        return $this->sourceIdpEntityIdAttribute;
    }

    public function getSideInfo(string $side)
    {
        if (!in_array($side, self::SIDES, true)) {
            throw new \Exception('Unrecognized side parameter value passed \'' . $side . '\'.');
        }

        return array_merge([
            'name' => '',
            'id' => '',
        ], $this->config->getArray($side, []));
    }

    public function getRequiredAuthSource()
    {
        return $this->requiredAuthSource;
    }

    public function getKeepPerUser()
    {
        return $this->keepPerUser;
    }

    public function isApiWriteEnabled()
    {
        return $this->apiWriteEnabled;
    }

    public function getApiWriteUsername()
    {
        return $this->apiWriteUsername;
    }

    public function getApiWritePasswordHash()
    {
        return $this->apiWritePasswordHash;
    }
}
