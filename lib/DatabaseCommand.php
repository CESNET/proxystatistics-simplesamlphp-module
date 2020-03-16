<?php

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Pavel Břoušek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\proxystatistics;

use PDO;
use SimpleSAML\Database;
use SimpleSAML\Logger;

class DatabaseCommand
{
    public const TABLE_SUM = 'statistics_sums';

    private const TABLE_PER_USER = 'statistics_per_user';

    private const TABLE_IDP = 'statistics_idp';

    private const TABLE_SP = 'statistics_sp';

    private const TABLE_SIDES = [
        Config::MODE_IDP => self::TABLE_IDP,
        Config::MODE_SP => self::TABLE_SP,
    ];

    private const TABLE_IDS = [
        self::TABLE_IDP => 'idpId',
        self::TABLE_SP => 'spId',
    ];

    private $tables = [
        self::TABLE_SUM => self::TABLE_SUM,
        self::TABLE_PER_USER => self::TABLE_PER_USER,
        self::TABLE_IDP => self::TABLE_IDP,
        self::TABLE_SP => self::TABLE_SP,
    ];

    private $config;

    private $conn = null;

    private $mode;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->conn = Database::getInstance($this->config->getStore());
        assert($this->conn !== null);
        $this->tables = array_merge($this->tables, $this->config->getTables());
        $this->mode = $this->config->getMode();
    }

    public static function prependColon($str)
    {
        return ':' . $str;
    }

    public function insertLogin(&$request, &$date)
    {
        $entities = $this->getEntities($request);

        foreach (Config::SIDES as $side) {
            if (empty($entities[$side]['id'])) {
                Logger::error('idpEntityId or spEntityId is empty and login log was not inserted into the database.');
                return;
            }
        }

        $idAttribute = $this->config->getIdAttribute();
        $userId = isset($request['Attributes'][$idAttribute]) ? $request['Attributes'][$idAttribute][0] : '';

        $ids = [];
        foreach (self::TABLE_SIDES as $side => $table) {
            $tableId = self::TABLE_IDS[$table];
            $ids[$tableId] = $this->getIdFromIdentifier($table, $entities[$side], $tableId);
        }

        if ($this->writeLogin($date, $ids, $userId) === false) {
            Logger::error('The login log was not inserted.');
        }
    }

    public function getNameById($side, $id)
    {
        $table = self::TABLE_SIDES[$side];
        return $this->read(
            'SELECT IFNULL(name, identifier) ' .
            'FROM ' . $this->tables[$table] . ' ' .
            'WHERE ' . self::TABLE_IDS[$table] . '=:id',
            ['id' => $id]
        )->fetchColumn();
    }

    public function getLoginCountPerDay($days, $where = [])
    {
        $params = [];
        $query = 'SELECT UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(year,"-",month,"-",day), "%Y-%m-%d")) AS day, ' .
                 'logins AS count, users ' .
                 'FROM ' . $this->tables[self::TABLE_SUM] . ' ' .
                 'WHERE ';
        $where = array_merge([Config::MODE_SP => null, Config::MODE_IDP => null], $where);
        self::addWhereId($where, $query, $params);
        self::addDaysRange($days, $query, $params);
        $query .= //'GROUP BY day ' .
                  'ORDER BY day ASC';

        return $this->read($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessCount($side, $days, $where = [])
    {
        $table = self::TABLE_SIDES[$side];
        $params = [];
        $query = 'SELECT IFNULL(name,identifier) AS name, ' . self::TABLE_IDS[$table] . ', SUM(logins) AS count ' .
                 'FROM ' . $this->tables[$table] . ' ' .
                 'LEFT OUTER JOIN ' . $this->tables[self::TABLE_SUM] . ' ' .
                 'USING (' . self::TABLE_IDS[$table] . ') ' .
                 'WHERE ';
        self::addWhereId($where, $query, $params);
        self::addDaysRange($days, $query, $params);
        $query .= 'GROUP BY ' . self::TABLE_IDS[$table] . ' ';
        $query .= 'ORDER BY SUM(logins) DESC';

        return $this->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    public function aggregate()
    {
        foreach ([self::TABLE_IDS[self::TABLE_IDP], null] as $idpId) {
            foreach ([self::TABLE_IDS[self::TABLE_SP], null] as $spId) {
                $ids = [$idpId, $spId];
                $msg = 'Aggregating daily statistics per ' . implode(' and ', array_filter($ids));
                Logger::info($msg);
                $query = 'INSERT INTO ' . $this->tables[self::TABLE_SUM] . ' '
                    . 'SELECT NULL, YEAR(`day`), MONTH(`day`), DAY(`day`), ';
                foreach ($ids as $id) {
                    $query .= ($id === null ? '0' : $id) . ',';
                }
                $query .= 'SUM(logins), COUNT(DISTINCT user) '
                    . 'FROM ' . $this->tables[self::TABLE_PER_USER] . ' '
                    . 'WHERE day<DATE(NOW()) '
                    . 'GROUP BY ' . self::getAggregateGroupBy($ids) . ' '
                    . 'ON DUPLICATE KEY UPDATE id=id;';
                // do nothing if row already exists
                if (!$this->conn->write($query)) {
                    Logger::warning($msg . ' failed');
                }
            }
        }

        $keepPerUserDays = $this->config->getKeepPerUser();

        $msg = 'Deleting detailed statistics';
        Logger::info($msg);
        // INNER JOIN ensures that only aggregated stats are deleted
        if (
            !$this->conn->write(
                'DELETE u FROM ' . $this->tables[self::TABLE_PER_USER] . ' AS u '
                . 'INNER JOIN ' . $this->tables[self::TABLE_SUM] . ' AS s '
                . 'ON YEAR(u.`day`)=s.`year` AND MONTH(u.`day`)=s.`month` AND DAY(u.`day`)=s.`day`'
                . 'WHERE u.`day` < CURDATE() - INTERVAL :days DAY',
                ['days' => $keepPerUserDays]
            )
        ) {
            Logger::warning($msg . ' failed');
        }
    }

    private function read($query, $params)
    {
        return $this->conn->read($query, $params);
    }

    private static function addWhereId($where, &$query, &$params)
    {
        assert(count(array_filter($where)) <= 1); //placeholder would be overwritten
        $parts = [];
        foreach ($where as $side => $value) {
            $table = self::TABLE_SIDES[$side];
            $column = self::TABLE_IDS[$table];
            $part = $column;
            if ($value === null) {
                $part .= '=0';
            } else {
                $part .= '=:id';
                $params['id'] = $value;
            }
            $parts[] = $part;
        }
        if (empty($parts)) {
            $parts[] = '1=1';
        }
        $query .= implode(' AND ', $parts);
        $query .= ' ';
    }

    private function writeLogin($date, $ids, $user)
    {
        $params = array_merge($ids, [
            'day' => $date->format('Y-m-d'),
            'logins' => 1,
            'user' => $user,
        ]);
        $fields = array_keys($params);
        $placeholders = array_map(['self', 'prependColon'], $fields);
        $query = 'INSERT INTO ' . $this->tables[self::TABLE_PER_USER] . ' (' . implode(', ', $fields) . ')' .
                 ' VALUES (' . implode(', ', $placeholders) . ') ON DUPLICATE KEY UPDATE logins = logins + 1';

        return $this->conn->write($query, $params);
    }

    private function getEntities($request)
    {
        $entities = [
            Config::MODE_IDP => [],
            Config::MODE_SP => [],
        ];
        if ($this->mode !== Config::MODE_IDP) {
            $entities[Config::MODE_IDP]['id'] = $request['saml:sp:IdP'];
            $entities[Config::MODE_IDP]['name'] = $request['Attributes']['sourceIdPName'][0];
        }
        if ($this->mode !== Config::MODE_SP) {
            $entities[Config::MODE_SP]['id'] = $request['Destination']['entityid'];
            $entities[Config::MODE_SP]['name'] = $request['Destination']['name']['en'] ?? '';
        }

        if ($this->mode !== Config::MODE_PROXY) {
            $entities[$this->mode] = $this->config->getSideInfo($this->mode);
            if (empty($entities[$this->mode]['id']) || empty($entities[$this->mode]['name'])) {
                Logger::error('Invalid configuration (id, name) for ' . $this->mode);
            }
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
        return $this->read('SELECT ' . $idColumn . ' FROM ' . $this->tables[$table]
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

    private static function getAggregateGroupBy($ids)
    {
        $columns = ['day'];
        foreach ($ids as $id) {
            if ($id !== null) {
                $columns[] = $id;
            }
        }
        return '`' . implode('`,`', $columns) . '`';
    }
}
