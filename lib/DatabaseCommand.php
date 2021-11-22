<?php

declare(strict_types=1);

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
        self::TABLE_IDP => 'idp_id',
        self::TABLE_SP => 'sp_id',
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

    private $escape_char = '`';

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->conn = Database::getInstance($this->config->getStore());
        if ($this->conn->getDriver() === 'pgsql') {
            $this->escape_char = '"';
        }
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
            'SELECT COALESCE(name, identifier) ' .
            'FROM ' . $this->tables[$table] . ' ' .
            'WHERE ' . self::TABLE_IDS[$table] . '=:id',
            [
                'id' => $id,
            ]
        )->fetchColumn();
    }

    public function getLoginCountPerDay($days, $where = [])
    {
        $params = [];
        if ($this->conn->getDriver() === 'pgsql') {
            $query = "SELECT EXTRACT(epoch FROM TO_DATE(CONCAT(year,'-',month,'-',day), 'YYYY-MM-DD')) AS day, ";
        } else {
            $query = "SELECT UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(year,'-',month,'-',day), '%Y-%m-%d')) AS day, ";
        }
        $query .= 'logins AS count, users ' .
                 'FROM ' . $this->tables[self::TABLE_SUM] . ' ' .
                 'WHERE ';
        $where = array_merge([
            Config::MODE_SP => null,
            Config::MODE_IDP => null,
        ], $where);
        $this->addWhereId($where, $query, $params);
        $this->addDaysRange($days, $query, $params);
        $query .= //'GROUP BY day ' .
                  'ORDER BY day ASC';

        return $this->read($query, $params)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessCount($side, $days, $where = [])
    {
        $table = self::TABLE_SIDES[$side];
        $params = [];
        $query = 'SELECT COALESCE(name,identifier) AS name, ' . self::TABLE_IDS[$table] . ', SUM(logins) AS count ' .
                 'FROM ' . $this->tables[$table] . ' ' .
                 'LEFT OUTER JOIN ' . $this->tables[self::TABLE_SUM] . ' ' .
                 'USING (' . self::TABLE_IDS[$table] . ') ' .
                 'WHERE ';
        $this->addWhereId($where, $query, $params);
        $this->addDaysRange($days, $query, $params);
        $query .= 'GROUP BY ' . self::TABLE_IDS[$table] . ' ';
        $query .= 'ORDER BY SUM(logins) DESC';

        return $this->read($query, $params)
            ->fetchAll(PDO::FETCH_NUM);
    }

    public function aggregate()
    {
        foreach ([self::TABLE_IDS[self::TABLE_IDP], null] as $idp_id) {
            foreach ([self::TABLE_IDS[self::TABLE_SP], null] as $sp_id) {
                $ids = [$idp_id, $sp_id];
                $msg = 'Aggregating daily statistics per ' . implode(' and ', array_filter($ids));
                Logger::info($msg);
                $query = 'INSERT INTO ' . $this->tables[self::TABLE_SUM] . ' '
                    . '(' . $this->escape_cols(['year', 'month', 'day', 'idp_id', 'sp_id', 'logins', 'users']) . ') '
                    . 'SELECT EXTRACT(YEAR FROM ' . $this->escape_col(
                        'day'
                    ) . '), EXTRACT(MONTH FROM ' . $this->escape_col(
                        'day'
                    ) . '), EXTRACT(DAY FROM ' . $this->escape_col('day') . '), ';
                foreach ($ids as $id) {
                    $query .= ($id === null ? '0' : $id) . ',';
                }
                $query .= 'SUM(logins), COUNT(DISTINCT ' . $this->escape_col('user') . ') '
                    . 'FROM ' . $this->tables[self::TABLE_PER_USER] . ' '
                    . 'WHERE day<DATE(NOW()) '
                    . 'GROUP BY ' . $this->getAggregateGroupBy($ids) . ' ';
                if ($this->conn->getDriver() === 'pgsql') {
                    $query .= 'ON CONFLICT (' . $this->escape_cols(
                        ['year', 'month', 'day', 'idp_id', 'sp_id']
                    ) . ') DO NOTHING;';
                } else {
                    $query .= 'ON DUPLICATE KEY UPDATE id=id;';
                }
                // do nothing if row already exists
                if (! $this->conn->write($query)) {
                    Logger::warning($msg . ' failed');
                }
            }
        }

        $keepPerUserDays = $this->config->getKeepPerUser();

        $msg = 'Deleting detailed statistics';
        Logger::info($msg);
        if ($this->conn->getDriver() === 'pgsql') {
            $make_date = 'MAKE_DATE(' . $this->escape_cols(['year', 'month', 'day']) . ')';
            $date_clause = sprintf('CURRENT_DATE - INTERVAL \'%s DAY\' ', $keepPerUserDays);
            $params = [];
        } else {
            $make_date = 'STR_TO_DATE(CONCAT(' . $this->escape_col('year') . ",'-'," . $this->escape_col(
                'month'
            ) . ",'-'," . $this->escape_col('day') . "), '%Y-%m-%d')";
            $date_clause = 'CURDATE() - INTERVAL :days DAY';
            $params = [
                'days' => $keepPerUserDays,
            ];
        }
        $query = 'DELETE FROM ' . $this->tables[self::TABLE_PER_USER] . ' WHERE ' . $this->escape_col(
            'day'
        ) . ' < ' . $date_clause
        . ' AND ' . $this->escape_col(
            'day'
        ) . ' IN (SELECT ' . $make_date . ' FROM ' . $this->tables[self::TABLE_SUM] . ')';
        if (
            ! $this->conn->write($query, $params)
        ) {
            Logger::warning($msg . ' failed');
        }
    }

    private function escape_col($col_name)
    {
        return $this->escape_char . $col_name . $this->escape_char;
    }

    private function escape_cols($col_names)
    {
        return $this->escape_char . implode(
            $this->escape_char . ',' . $this->escape_char,
            $col_names
        ) . $this->escape_char;
    }

    private function read($query, $params)
    {
        return $this->conn->read($query, $params);
    }

    private function addWhereId($where, &$query, &$params)
    {
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
        if (empty($user)) {
            return false;
        }
        $params = array_merge($ids, [
            'day' => $date->format('Y-m-d'),
            'logins' => 1,
            'user' => $user,
        ]);
        $fields = array_keys($params);
        $placeholders = array_map(['self', 'prependColon'], $fields);
        $query = 'INSERT INTO ' . $this->tables[self::TABLE_PER_USER] . ' (' . $this->escape_cols($fields) . ')' .
                 ' VALUES (' . implode(', ', $placeholders) . ') ';
        if ($this->conn->getDriver() === 'pgsql') {
            $query .= 'ON CONFLICT (' . $this->escape_cols(
                ['day', 'idp_id', 'sp_id', 'user']
            ) . ') DO UPDATE SET "logins" = ' . $this->tables[self::TABLE_PER_USER] . '.logins + 1;';
        } else {
            $query .= 'ON DUPLICATE KEY UPDATE logins = logins + 1;';
        }

        return $this->conn->write($query, $params);
    }

    private function getEntities($request)
    {
        $entities = [
            Config::MODE_IDP => [],
            Config::MODE_SP => [],
        ];
        if ($this->mode !== Config::MODE_IDP && $this->mode !== Config::MODE_MULTI_IDP) {
            $entities[Config::MODE_IDP]['id'] = $request['saml:sp:IdP'];
            $entities[Config::MODE_IDP]['name'] = $request['Attributes']['sourceIdPName'][0];
        }
        if ($this->mode !== Config::MODE_SP) {
            $entities[Config::MODE_SP]['id'] = $request['Destination']['entityid'];
            if (isset($request['Destination']['UIInfo']['DisplayName']['en'])) {
                $entities[Config::MODE_SP]['name'] = $request['Destination']['UIInfo']['DisplayName']['en'];
            } else {
                $entities[Config::MODE_SP]['name'] = $request['Destination']['name']['en'] ?? '';
            }
        }

        if ($this->mode !== Config::MODE_PROXY && $this->mode !== Config::MODE_MULTI_IDP) {
            $entities[$this->mode] = $this->config->getSideInfo($this->mode);
            if (empty($entities[$this->mode]['id']) || empty($entities[$this->mode]['name'])) {
                Logger::error('Invalid configuration (id, name) for ' . $this->mode);
            }
        }

        if ($this->mode === Config::MODE_MULTI_IDP) {
            $entities[Config::MODE_IDP] = $this->config->getSideInfo(Config::MODE_IDP);
            if (empty($entities[Config::MODE_IDP]['id']) || empty($entities[Config::MODE_IDP]['name'])) {
                Logger::error('Invalid configuration (id, name) for ' . $this->mode);
            }
        }

        return $entities;
    }

    private function getIdFromIdentifier($table, $entity, $idColumn)
    {
        $identifier = $entity['id'];
        $name = $entity['name'];
        $query = 'INSERT INTO ' . $this->tables[$table] . '(identifier, name) VALUES (:identifier, :name1) ';
        if ($this->conn->getDriver() === 'pgsql') {
            $query .= 'ON CONFLICT (identifier) DO UPDATE SET name = :name2;';
        } else {
            $query .= 'ON DUPLICATE KEY UPDATE name = :name2';
        }
        $this->conn->write($query, [
            'identifier' => $identifier,
            'name1' => $name,
            'name2' => $name,
        ]);
        return $this->read('SELECT ' . $idColumn . ' FROM ' . $this->tables[$table]
            . ' WHERE identifier=:identifier', [
                'identifier' => $identifier,
            ])
            ->fetchColumn();
    }

    private function addDaysRange($days, &$query, &$params, $not = false)
    {
        if ($days !== 0) {    // 0 = all time
            if (stripos($query, 'WHERE') === false) {
                $query .= 'WHERE';
            } else {
                $query .= 'AND';
            }
            if ($this->conn->getDriver() === 'pgsql') {
                $query .= ' MAKE_DATE(year,month,day) ';
            } else {
                $query .= " CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) ";
            }
            if ($not) {
                $query .= 'NOT ';
            }
            if ($this->conn->getDriver() === 'pgsql') {
                if (! is_int($days) && ! ctype_digit($days)) {
                    throw new \Exception('days have to be an integer');
                }
                $query .= sprintf('BETWEEN CURRENT_DATE - INTERVAL \'%s DAY\' AND CURRENT_DATE ', $days);
            } else {
                $query .= 'BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ';
                $params['days'] = $days;
            }
        }
    }

    private function getAggregateGroupBy($ids)
    {
        $columns = ['day'];
        foreach ($ids as $id) {
            if ($id !== null) {
                $columns[] = $id;
            }
        }
        return $this->escape_cols($columns);
    }
}
