<?php

declare(strict_types=1);

namespace SimpleSAML\Module\proxystatistics;

use Exception;
use PDO;
use PDOStatement;
use SimpleSAML\Database;
use SimpleSAML\Logger;

class DatabaseCommand
{
    public const TABLE_SUM = 'statistics_sums';
    private const DEBUG_PREFIX = 'proxystatistics:DatabaseCommand - ';

    private const TABLE_PER_USER = 'statistics_per_user';

    private const TABLE_IDP = 'statistics_idp';

    private const TABLE_SP = 'statistics_sp';

    private const KEY_ID = 'id';

    private const KEY_NAME = 'name';

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

    private $conn;

    private $mode;

    private $escape_char = '`';

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->conn = Database::getInstance($this->config->getStore());
        if ($this->isPgsql()) {
            $this->escape_char = '"';
        } elseif ($this->isMysql()) {
            $this->escape_char = '`';
        } else {
            $this->unknownDriver();
        }
        $this->tables = array_merge($this->tables, $this->config->getTables());
        $this->mode = $this->config->getMode();
    }

    public function insertLogin($request, &$date)
    {
        $entities = $this->prepareEntitiesData($request);

        foreach (Config::SIDES as $side) {
            if (empty($entities[$side][self::KEY_ID])) {
                Logger::error(
                    self::DEBUG_PREFIX . 'idpEntityId or spEntityId is empty and login log was not inserted into the database.'
                );

                return;
            }
        }

        $userId = $this->getUserId($request);

        $ids = [];
        foreach (self::TABLE_SIDES as $side => $table) {
            $tableId = self::TABLE_IDS[$table];
            $ids[$tableId] = $this->getEntityDbIdFromEntityIdentifier($table, $entities[$side], $tableId);
        }

        if (false === $this->writeLogin($date, $ids, $userId)) {
            Logger::error(self::DEBUG_PREFIX . 'login record has not been inserted (data \'' . json_encode([
                'user' => $userId,
                'ids' => $ids,
                'date' => $date,
            ]) . '\'.');
        }
    }

    public function getEntityNameByEntityIdentifier($side, $id)
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
        if ($this->isPgsql()) {
            $query = "SELECT EXTRACT(epoch FROM TO_DATE(CONCAT(year,'-',month,'-',day), 'YYYY-MM-DD')) AS day, ";
        } elseif ($this->isMysql()) {
            $query = "SELECT UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(year,'-',month,'-',day), '%Y-%m-%d')) AS day, ";
        } else {
            $this->unknownDriver();
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
            ->fetchAll(PDO::FETCH_ASSOC)
        ;
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
            ->fetchAll(PDO::FETCH_NUM)
        ;
    }

    public function aggregate()
    {
        foreach ([self::TABLE_IDS[self::TABLE_IDP], null] as $idp_id) {
            foreach ([self::TABLE_IDS[self::TABLE_SP], null] as $sp_id) {
                $ids = [$idp_id, $sp_id];
                $msg = 'Aggregating daily statistics per ' . implode(' and ', array_filter($ids));
                Logger::info(self::DEBUG_PREFIX . $msg);
                $query = 'INSERT INTO ' . $this->tables[self::TABLE_SUM] . ' '
                    . '(' . $this->escape_cols(['year', 'month', 'day', 'idp_id', 'sp_id', 'logins', 'users']) . ') '
                    . 'SELECT EXTRACT(YEAR FROM ' . $this->escape_col(
                        'day'
                    ) . '), EXTRACT(MONTH FROM ' . $this->escape_col(
                        'day'
                    ) . '), EXTRACT(DAY FROM ' . $this->escape_col('day') . '), ';
                foreach ($ids as $id) {
                    $query .= (null === $id ? '0' : $id) . ',';
                }
                $query .= 'SUM(logins), COUNT(DISTINCT ' . $this->escape_col('user') . ') '
                    . 'FROM ' . $this->tables[self::TABLE_PER_USER] . ' '
                    . 'WHERE day<DATE(NOW()) '
                    . 'GROUP BY ' . $this->getAggregateGroupBy($ids) . ' ';
                if ($this->isPgsql()) {
                    $query .= 'ON CONFLICT (' . $this->escape_cols(
                        ['year', 'month', 'day', 'idp_id', 'sp_id']
                    ) . ') DO NOTHING;';
                } elseif ($this->isMysql()) {
                    $query .= 'ON DUPLICATE KEY UPDATE id=id;';
                } else {
                    $this->unknownDriver();
                }
                // do nothing if row already exists
                if (!$this->conn->write($query)) {
                    Logger::warning(self::DEBUG_PREFIX . $msg . ' failed');
                }
            }
        }

        $keepPerUserDays = $this->config->getKeepPerUser();

        $msg = 'Deleting detailed statistics';
        Logger::info(self::DEBUG_PREFIX . $msg);
        if ($this->isPgsql()) {
            $make_date = 'MAKE_DATE(' . $this->escape_cols(['year', 'month', 'day']) . ')';
            $date_clause = sprintf('CURRENT_DATE - INTERVAL \'%s DAY\' ', $keepPerUserDays);
            $params = [];
        } elseif ($this->isMysql()) {
            $make_date = 'STR_TO_DATE(CONCAT(' . $this->escape_col('year') . ",'-'," . $this->escape_col(
                'month'
            ) . ",'-'," . $this->escape_col('day') . "), '%Y-%m-%d')";
            $date_clause = 'CURDATE() - INTERVAL :days DAY';
            $params = [
                'days' => $keepPerUserDays,
            ];
        } else {
            $this->unknownDriver();
        }
        $query = 'DELETE FROM ' . $this->tables[self::TABLE_PER_USER] . ' WHERE ' . $this->escape_col(
            'day'
        ) . ' < ' . $date_clause
        . ' AND ' . $this->escape_col(
            'day'
        ) . ' IN (SELECT ' . $make_date . ' FROM ' . $this->tables[self::TABLE_SUM] . ')';
        $written = $this->conn->write($query, $params);
        if (is_bool($written) && !$written) {
            Logger::warning(self::DEBUG_PREFIX . $msg . ' failed');
        } elseif (0 === $written) {
            Logger::warning(self::DEBUG_PREFIX . $msg . ' completed, but updated 0 rows.');
        } else {
            Logger::info(self::DEBUG_PREFIX . $msg . ' completed and updated ' . $written . ' rows.');
        }
    }

    public static function prependColon($str): string
    {
        return ':' . $str;
    }

    private function writeLogin($date, $ids, $user): bool
    {
        if (empty($user)) {
            Logger::warning(self::DEBUG_PREFIX . 'user is unknown, cannot insert login. Ending prematurely.');

            return false;
        }
        if (empty($ids[self::TABLE_IDS[self::TABLE_IDP]]) || empty($ids[self::TABLE_IDS[self::TABLE_SP]])) {
            Logger::warning(
                self::DEBUG_PREFIX . 'no IDP_ID or SP_ID has been provided, cannot insert login. Ending prematurely.'
            );

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
        if ($this->isPgsql()) {
            $query .= 'ON CONFLICT (' . $this->escape_cols(
                ['day', 'idp_id', 'sp_id', 'user']
            ) . ') DO UPDATE SET "logins" = ' . $this->tables[self::TABLE_PER_USER] . '.logins + 1;';
        } elseif ($this->isMysql()) {
            $query .= 'ON DUPLICATE KEY UPDATE logins = logins + 1;';
        } else {
            $this->unknownDriver();
        }

        $written = $this->conn->write($query, $params);
        if (is_bool($written) && !$written) {
            Logger::debug(self::DEBUG_PREFIX . 'login entry write has failed.');

            return false;
        }
        if (0 === $written) {
            Logger::debug(self::DEBUG_PREFIX . 'login entry has been inserted, but has updated 0 rows.');

            return false;
        }

        return true;
    }

    private function prepareEntitiesData($request): array
    {
        $entities = [
            Config::MODE_IDP => [],
            Config::MODE_SP => [],
        ];
        if (Config::MODE_IDP !== $this->mode && Config::MODE_MULTI_IDP !== $this->mode) {
            $entities[Config::MODE_IDP][self::KEY_ID] = $this->getIdpIdentifier($request);
            $entities[Config::MODE_IDP][self::KEY_NAME] = $this->getIdpName($request);
        }
        if (Config::MODE_SP !== $this->mode) {
            $entities[Config::MODE_SP][self::KEY_ID] = $this->getSpIdentifier($request);
            $entities[Config::MODE_SP][self::KEY_NAME] = $this->getSpName($request);
        }

        if (Config::MODE_PROXY !== $this->mode && Config::MODE_MULTI_IDP !== $this->mode) {
            $entities[$this->mode] = $this->config->getSideInfo($this->mode);
            if (empty($entities[$this->mode][self::KEY_ID]) || empty($entities[$this->mode][self::KEY_NAME])) {
                Logger::error(self::DEBUG_PREFIX . 'Invalid configuration (id, name) for ' . $this->mode);
            }
        }

        if (Config::MODE_MULTI_IDP === $this->mode) {
            $entities[Config::MODE_IDP] = $this->config->getSideInfo(Config::MODE_IDP);
            if (empty($entities[Config::MODE_IDP][self::KEY_ID]) || empty($entities[Config::MODE_IDP][self::KEY_NAME])) {
                Logger::error(self::DEBUG_PREFIX . 'Invalid configuration (id, name) for ' . $this->mode);
            }
        }

        return $entities;
    }

    private function getEntityDbIdFromEntityIdentifier($table, $entity, $idColumn)
    {
        $identifier = $entity[self::KEY_ID];
        $name = $entity[self::KEY_NAME];
        $query = 'INSERT INTO ' . $this->tables[$table] . '(identifier, name) VALUES (:identifier, :name1) ';
        if ($this->isPgsql()) {
            $query .= 'ON CONFLICT (identifier) DO UPDATE SET name = :name2;';
        } elseif ($this->isMysql()) {
            $query .= 'ON DUPLICATE KEY UPDATE name = :name2';
        } else {
            $this->unknownDriver();
        }
        $this->conn->write($query, [
            'identifier' => $identifier,
            'name1' => $name,
            'name2' => $name,
        ]);

        return $this->read('SELECT ' . $idColumn . ' FROM ' . $this->tables[$table]
            . ' WHERE identifier=:identifier', [
                'identifier' => $identifier,
            ])->fetchColumn();
    }

    // Query construction helper methods

    private function addWhereId($where, &$query, &$params)
    {
        $parts = [];
        foreach ($where as $side => $value) {
            $table = self::TABLE_SIDES[$side];
            $column = self::TABLE_IDS[$table];
            $part = $column;
            if (null === $value) {
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

    private function addDaysRange($days, &$query, &$params, $not = false)
    {
        if (0 !== $days) {    // 0 = all time
            if (false === stripos($query, 'WHERE')) {
                $query .= 'WHERE';
            } else {
                $query .= 'AND';
            }
            if ($this->isPgsql()) {
                $query .= ' MAKE_DATE(year,month,day) ';
            } elseif ($this->isMysql()) {
                $query .= " CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) ";
            } else {
                $this->unknownDriver();
            }
            if ($not) {
                $query .= 'NOT ';
            }
            if ($this->isPgsql()) {
                if (!is_int($days) && !ctype_digit($days)) {
                    throw new Exception('days have to be an integer');
                }
                $query .= sprintf('BETWEEN CURRENT_DATE - INTERVAL \'%s DAY\' AND CURRENT_DATE ', $days);
            } else {
                $query .= 'BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ';
                $params['days'] = $days;
            }
        }
    }

    private function escape_col($col_name): string
    {
        return $this->escape_char . $col_name . $this->escape_char;
    }

    private function escape_cols($col_names): string
    {
        return $this->escape_char . implode(
            $this->escape_char . ',' . $this->escape_char,
            $col_names
        ) . $this->escape_char;
    }

    private function getAggregateGroupBy($ids): string
    {
        $columns = ['day'];
        foreach ($ids as $id) {
            if (null !== $id) {
                $columns[] = $id;
            }
        }

        return $this->escape_cols($columns);
    }

    private function getIdpIdentifier($request)
    {
        $sourceIdpEntityIdAttribute = $this->config->getSourceIdpEntityIdAttribute();
        if (!empty($sourceIdpEntityIdAttribute) && !empty($request['Attributes'][$sourceIdpEntityIdAttribute][0])) {
            return $request['Attributes'][$sourceIdpEntityIdAttribute][0];
        }

        return $request['saml:sp:IdP'];
    }

    private function getUserId($request)
    {
        $idAttribute = $this->config->getIdAttribute();

        return isset($request['Attributes'][$idAttribute]) ? $request['Attributes'][$idAttribute][0] : '';
    }

    private function getIdpName($request)
    {
        return $request['Attributes']['sourceIdPName'][0];
    }

    private function getSpIdentifier($request)
    {
        return $request['Destination']['entityid'];
    }

    private function getSpName($request)
    {
        $displayName = $request['Destination']['UIInfo']['DisplayName']['en'] ?? '';
        if (empty($displayName)) {
            $displayName = $request['Destination']['name']['en'] ?? '';
        }

        return $displayName;
    }

    private function read($query, $params): PDOStatement
    {
        return $this->conn->read($query, $params);
    }

    private function isPgsql(): bool
    {
        return 'pgsql' === $this->conn->getDriver();
    }

    private function isMysql(): bool
    {
        return 'mysql' === $this->conn->getDriver();
    }

    private function unknownDriver()
    {
        Logger::error(self::DEBUG_PREFIX . 'unsupported DB driver \'' . $this->conn->getDriver());
        throw new Exception('Unsupported DB driver');
    }
}
