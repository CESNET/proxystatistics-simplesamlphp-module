<?php

namespace SimpleSAML\Module\proxystatistics;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use PDO;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class DatabaseCommand
{
    private $databaseConnector;
    private $conn;
    private $statisticsTableName;
    private $detailedStatisticsTableName;
    private $identityProvidersMapTableName;
    private $serviceProvidersMapTableName;

    public function __construct()
    {
        $this->databaseConnector = new DatabaseConnector();
        $this->conn = $this->databaseConnector->getConnection();
        assert($this->conn !== null);
        $this->statisticsTableName = $this->databaseConnector->getStatisticsTableName();
        $this->detailedStatisticsTableName = $this->databaseConnector->getDetailedStatisticsTableName();
        $this->identityProvidersMapTableName = $this->databaseConnector->getIdentityProvidersMapTableName();
        $this->serviceProvidersMapTableName = $this->databaseConnector->getServiceProvidersMapTableName();
    }

    private function writeLogin($year, $month, $day, $sourceIdp, $service, $user = null)
    {
        $params = [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'sourceIdp' => $sourceIdp,
            'service' => $service,
            'count' => 1,
        ];
        $table = $this->statisticsTableName;
        if ($user && $this->databaseConnector->getDetailedDays() > 0) {
            // write also into aggregated statistics
            self::writeLogin($year, $month, $day, $sourceIdp, $service);
            $params['user'] = $user;
            $table = $this->detailedStatisticsTableName;
        }
        $fields = array_keys($params);
        $placeholders = array_map(function ($field) {
            return ':' . $field;

        }, $fields);
        $query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ")" .
                 " VALUES (" . implode(', ', $placeholders) . ") ON DUPLICATE KEY UPDATE count = count + 1";

        return $this->conn->write($query, $params);
    }

    public function insertLogin(&$request, &$date)
    {
        if (!in_array($this->databaseConnector->getMode(), ['PROXY', 'IDP', 'SP'])) {
            throw new Exception('Unknown mode is set. Mode has to be one of the following: PROXY, IDP, SP.');
        }
        if ($this->databaseConnector->getMode() !== 'IDP') {
            $idpName = $request['Attributes']['sourceIdPName'][0];
            $idpEntityID = $request['saml:sp:IdP'];
        }
        if ($this->databaseConnector->getMode() !== 'SP') {
            $spEntityId = $request['Destination']['entityid'];
            $spName = isset($request['Destination']['name']) ? $request['Destination']['name']['en'] : '';
        }

        if ($this->databaseConnector->getMode() === 'IDP') {
            $idpName = $this->databaseConnector->getIdpName();
            $idpEntityID = $this->databaseConnector->getIdpEntityId();
        } elseif ($this->databaseConnector->getMode() === 'SP') {
            $spEntityId = $this->databaseConnector->getSpEntityId();
            $spName = $this->databaseConnector->getSpName();
        }

        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');

        if (empty($idpEntityID) || empty($spEntityId)) {
            Logger::error(
                "'idpEntityId' or 'spEntityId'" .
                " is empty and login log wasn't inserted into the database."
            );
        } else {
            $idAttribute = $this->databaseConnector->getUserIdAttribute();
            $userId = isset($request['Attributes'][$idAttribute]) ? $request['Attributes'][$idAttribute][0] : null;
            if ($this->writeLogin($year, $month, $day, $idpEntityID, $spEntityId, $userId) === false) {
                Logger::error("The login log wasn't inserted into table: " . $this->statisticsTableName . ".");
            }

            if (!empty($idpName)) {
                $this->conn->write(
                    "INSERT INTO " . $this->identityProvidersMapTableName .
                    "(entityId, name) VALUES (:idp, :name1) ON DUPLICATE KEY UPDATE name = :name2",
                    ['idp'=>$idpEntityID, 'name1'=>$idpName, 'name2'=>$idpName]
                );
            }

            if (!empty($spName)) {
                $this->conn->write(
                    "INSERT INTO " . $this->serviceProvidersMapTableName .
                    "(identifier, name) VALUES (:sp, :name1) ON DUPLICATE KEY UPDATE name = :name2",
                    ['sp'=>$spEntityId, 'name1'=>$spName, 'name2'=>$spName]
                );
            }
        }

    }

    public function getSpNameBySpIdentifier($identifier)
    {
        return $this->conn->read(
            "SELECT name " .
            "FROM " . $this->serviceProvidersMapTableName . " " .
            "WHERE identifier=:sp",
            ['sp'=>$identifier]
        )->fetchColumn();
    }

    public function getIdPNameByEntityId($idpEntityId)
    {
        return $this->conn->read(
            "SELECT name " .
            "FROM " . $this->identityProvidersMapTableName . " " .
            "WHERE entityId=:idp",
            ['idp'=>$idpEntityId]
        )->fetchColumn();
    }

    public function getLoginCountPerDay($days)
    {
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $this->statisticsTableName . " " .
                 "WHERE service != '' ";
        $params = [];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLoginCountPerDayForService($days, $spIdentifier)
    {
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $this->statisticsTableName . " " .
                 "WHERE service=:service ";
        $params = ['service' => $spIdentifier];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLoginCountPerDayForIdp($days, $idpIdentifier)
    {
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $this->statisticsTableName . " " .
                 "WHERE sourceIdP=:sourceIdP ";
        $params = ['sourceIdP'=>$idpIdentifier];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessCountPerService($days)
    {
        $query = "SELECT IFNULL(name,service) AS spName, service, SUM(count) AS count " .
                 "FROM " . $this->serviceProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $this->statisticsTableName . " ON service = identifier ";
        $params = [];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY service HAVING service != '' " .
                  "ORDER BY count DESC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    public function getAccessCountForServicePerIdentityProviders($days, $spIdentifier)
    {
        $query = "SELECT IFNULL(name,sourceIdp) AS idpName, SUM(count) AS count " .
                 "FROM " . $this->identityProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $this->statisticsTableName . " ON sourceIdp = entityId ";
        $params = ['service' => $spIdentifier];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY sourceIdp, service HAVING sourceIdp != '' AND service=:service " .
                  "ORDER BY count DESC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    public function getAccessCountForIdentityProviderPerServiceProviders($days, $idpEntityId)
    {
        $query = "SELECT IFNULL(name,service) AS spName, SUM(count) AS count " .
                 "FROM " . $this->serviceProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $this->statisticsTableName . " ON service = identifier ";
        $params = ['sourceIdp'=>$idpEntityId];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY sourceIdp, service HAVING service != '' AND sourceIdp=:sourceIdp " .
                  "ORDER BY count DESC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    public function getLoginCountPerIdp($days)
    {
        $query = "SELECT IFNULL(name,sourceIdp) AS idpName, sourceIdp, SUM(count) AS count " .
                 "FROM " . $this->identityProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $this->statisticsTableName . " ON sourceIdp = entityId ";
        $params = [];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY sourceIdp HAVING sourceIdp != '' " .
                  "ORDER BY count DESC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    private static function addDaysRange($days, &$query, &$params, $not = false)
    {
        if ($days != 0) {    // 0 = all time
            if (stripos($query, "WHERE") === false) {
                $query .= "WHERE";
            } else {
                $query .= "AND";
            }
            $query .= " CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) ";
            if ($not) {
                $query .= "NOT ";
            }
            $query .= "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            $params['days'] = $days;
        }
    }

    public function deleteOldDetailedStatistics()
    {
        if ($this->databaseConnector->getDetailedDays() > 0) {
            $query = "DELETE FROM " . $this->detailedStatisticsTableName . " ";
            $params = [];
            self::addDaysRange($this->databaseConnector->getDetailedDays(), $query, $params, true);
            return $this->conn->write($query, $params);
        }
    }
}
