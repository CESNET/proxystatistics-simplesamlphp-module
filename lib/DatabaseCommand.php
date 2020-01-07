<?php

namespace SimpleSAML\Module\proxystatistics;

use PDO;
use SimpleSAML\Logger;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class DatabaseCommand
{
    public const CONFIG_FILE_NAME = 'module_statisticsproxy.php';

    private const STORE = 'store';

    private const MODE = 'mode';

    private const IDP_ENTITY_ID = 'idpEntityId';

    private const IDP_NAME = 'idpName';

    private const SP_ENTITY_ID = 'spEntityId';

    private const SP_NAME = 'spName';

    private const USER_ID_ATTRIBUTE = 'userIdAttribute';

    private const TABLE_SUM = 'statistics_sums';

    private const TABLE_PER_USER = 'statistics_per_user';

    private const TABLE_IDP = 'statistics_idp';

    private const TABLE_SP = 'statistics_sp';

    private const TABLE_IDS = [
        self::TABLE_IDP => 'idpId',
        self::TABLE_SP => 'spId',
    ];

    private $config;

    private $conn = null;

    private $tables = [
        self::TABLE_SUM => self::TABLE_SUM,
        self::TABLE_PER_USER => self::TABLE_PER_USER,
        self::TABLE_IDP => self::TABLE_IDP,
        self::TABLE_SP => self::TABLE_SP,
    ];

    private $mode;

    public function __construct()
    {
        $this->config = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->conn = Database::getInstance($this->config->getConfigItem(self::STORE, null));
        assert($this->conn !== null);
        $this->tables = array_merge($this->tables, $this->config->getArray('tables', []));
        $this->mode = $this->config->getValueValidate(self::MODE, ['PROXY', 'IDP', 'SP'], 'PROXY');
    }

    public static function prependColon($str)
    {
        return ':' . $str;
    }

    public function insertLogin(&$request, &$date)
    {
        $entities = $this->getEntities($request);

        if (empty($entities['idp']['id']) || empty($entities['sp']['id'])) {
            Logger::error('idpEntityId or spEntityId is empty and login log was not inserted into the database.');
        } else {
            $idAttribute = $this->config->getString(self::USER_ID_ATTRIBUTE, 'uid');
            $userId = isset($request['Attributes'][$idAttribute]) ? $request['Attributes'][$idAttribute][0] : '';

            $ids = [];
            foreach (self::TABLE_IDS as $table => $tableId) {
                $ids[$tableId] = $this->getIdFromIdentifier($table, $entities[$table], $tableId);
            }

            if ($this->writeLogin($date, $ids, $userId) === false) {
                Logger::error('The login log was not inserted.');
            }
        }
    }

    public function getNameByIdentifier($table, $identifier)
    {
        return $this->conn->read(
            'SELECT name ' .
            'FROM ' . $this->tables[$table] . ' ' .
            'WHERE identifier=:id',
            ['id' => $identifier]
        )->fetchColumn();
    }

    public function getLoginCountPerDay($days, $where)
    {
        $params = [];
        $query = 'SELECT UNIX_TIMESTAMP(day) AS day, logins AS count ' .
                 'FROM ' . $this->tables[self::TABLE_SUM] . ' ' .
                 'WHERE ';
        self::addWhereId($where, $query, $params);
        self::addDaysRange($days, $query, $params);
        $query .= 'GROUP BY day ' .
                  'ORDER BY day ASC';

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessCount($table, $days, $where)
    {
        $params = [];
        $query = 'SELECT IFNULL(name,identifier) AS name, identifier, logins AS count ' .
                 'FROM ' . $this->tables[$table] . ' ' .
                 'LEFT OUTER JOIN ' . $this->tables[self::TABLE_SUM] . ' USING (idpId, spId) ' .
                 'WHERE ';
        self::addWhereId($where, $query, $params);
        self::addDaysRange($days, $query, $params);
        $query .= 'ORDER BY logins DESC';

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    public function deleteOldDetailedStatistics()
    {
        if ($this->config->getDetailedDays() > 0) {
            $query = 'DELETE FROM ' . $this->detailedStatisticsTableName . ' ';
            $params = [];
            self::addDaysRange($this->config->getDetailedDays(), $query, $params, true);
            return $this->conn->write($query, $params);
        }
    }

    private static function addWhereId($where, &$query, &$params)
    {
        $column = key($where);
        $query .= $column;
        if (empty($where[$column])) {
            $query .= '!=""'; // IS NOT NULL?
        } else {
            $query .= '=:id AND ' . ($column === 'idpId' ? 'spId' : 'idpId') . ' IS NULL';
            $params['id'] = $where[$column];
        }
        $query .= ' ';
    }

    private function writeLogin($date, $ids, $user)
    {
        $params = array_merge($ids, [
            'day' => $date->format('Y-m-d'),
            'count' => 1,
            'user' => $user,
        ]);
        $fields = array_keys($params);
        $placeholders = array_map(['DatabaseCommand', 'prependColon'], $fields);
        $query = 'INSERT INTO ' . $this->tables[self::TABLE_PER_USER] . ' (' . implode(', ', $fields) . ')' .
                 ' VALUES (' . implode(', ', $placeholders) . ') ON DUPLICATE KEY UPDATE count = count + 1';

        return $this->conn->write($query, $params);
    }

    private function getEntities($request)
    {
        $entities = [
            self::TABLE_IDP => [],
            self::TABLE_SP => [],
        ];
        if ($this->mode !== self::TABLE_IDP) {
            $entities[self::TABLE_IDP]['id'] = $request['saml:sp:IdP'];
            $entities[self::TABLE_IDP]['name'] = $request['Attributes']['sourceIdPName'][0];
        }
        if ($this->mode !== self::TABLE_SP) {
            $entities[self::TABLE_SP]['id'] = $request['Destination']['entityid'];
            $entities[self::TABLE_SP]['name'] = $request['Destination']['name']['en'] ?? '';
        }

        if ($this->mode === self::TABLE_IDP) {
            $entities[self::TABLE_IDP]['id'] = $this->config->getString(self::IDP_ENTITY_ID, '');
            $entities[self::TABLE_IDP]['name'] = $this->config->getString(self::IDP_NAME, '');
        } elseif ($this->mode === self::TABLE_SP) {
            $entities[self::TABLE_SP]['id'] = $this->config->getString(self::SP_ENTITY_ID, '');
            $entities[self::TABLE_SP]['name'] = $this->config->getString(self::SP_NAME, '');
        }
        return $entities;
    }

    private function getIdFromIdentifier($table, $entity, $idColumn)
    {
        $identifier = $entity['id'];
        $name = $entity['name'];
        $this->conn->write(
            'INSERT INTO ' . $this->tables[$table]
            . '(identifier, name) VALUES (:identifier, :name1) ON DUPLICATE KEY UPDATE name = :name2',
            ['identifier' => $identifier, 'name1' => $name, 'name2' => $name]
        );
        return $this->conn->read('SELECT ' . $idColumn . ' FROM ' . $this->tables[$table]
            . ' WHERE identifier=:identifier', ['identifier' => $identifier])
            ->fetchColumn();
    }

    private static function addDaysRange($days, &$query, &$params, $not = false)
    {
        if ($days !== 0) {    // 0 = all time
            if (stripos($query, 'WHERE') === false) {
                $query .= 'WHERE';
            } else {
                $query .= 'AND';
            }
            $query .= ' CONCAT(year,"-",LPAD(month,2,"00"),"-",LPAD(day,2,"00")) ';
            if ($not) {
                $query .= 'NOT ';
            }
            $query .= 'BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ';
            $params['days'] = $days;
        }
    }
}
