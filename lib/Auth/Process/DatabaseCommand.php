<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class DatabaseCommand
{
    private $databaseConnector;
    private $conn;
    private $statisticsTableName;
    private $identityProvidersMapTableName;
    private $serviceProvidersMapTableName;

    public function __construct()
    {
        $this->databaseConnector = new DatabaseConnector();
        $this->conn = $this->databaseConnector->getConnection();
        assert($this->conn != null);
        $this->statisticsTableName = $this->databaseConnector->getStatisticsTableName();
        $this->identityProvidersMapTableName = $this->databaseConnector->getIdentityProvidersMapTableName();
        $this->serviceProvidersMapTableName = $this->databaseConnector->getServiceProvidersMapTableName();
    }

    public function __destruct()
    {
        $this->databaseConnector->closeConnection();
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

        if (!in_array($databaseConnector->getMode(), ['PROXY', 'IDP', 'SP'])) {
            throw new Exception('Unknown mode is set. Mode has to be one of the following: PROXY, IDP, SP.');
        }

        if ($databaseConnector->getMode() !== 'IDP') {
            $idpName = $request['Attributes']['sourceIdPName'][0];
            $idpEntityID = $request['saml:sp:IdP'];
        }
        if ($databaseConnector->getMode() !== 'SP') {
            $spEntityId = $request['Destination']['entityid'];
            $spName = isset($request['Destination']['name']) ? $request['Destination']['name']['en'] : '';
        }

        if ($databaseConnector->getMode() === 'IDP') {
            $idpName = $databaseConnector->getIdpName();
            $idpEntityID = $databaseConnector->getIdpEntityId();
        } elseif ($databaseConnector->getMode() === 'SP') {
            $spEntityId = $databaseConnector->getSpEntityId();
            $spName = $databaseConnector->getSpName();
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
            $stmt = $this->conn->prepare(
                "INSERT INTO " . $this->statisticsTableName . "(year, month, day, sourceIdp, service, count)" .
                " VALUES (?, ?, ?, ?, ?, '1') ON DUPLICATE KEY UPDATE count = count + 1"
            );
            $stmt->bind_param("iiiss", $year, $month, $day, $idpEntityID, $spEntityId);
            if ($stmt->execute() === false) {
                Logger::error("The login log wasn't inserted into table: " . $this->statisticsTableName . ".");
            }

            if (!empty($idpName)) {
                $stmt->prepare(
                    "INSERT INTO " . $this->identityProvidersMapTableName .
                    "(entityId, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = ?"
                );
                $stmt->bind_param("sss", $idpEntityID, $idpName, $idpName);
                $stmt->execute();
            }

            if (!empty($spName)) {
                $stmt->prepare(
                    "INSERT INTO " . $this->serviceProvidersMapTableName .
                    "(identifier, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = ?"
                );
                $stmt->bind_param("sss", $spEntityId, $spName, $spName);
                $success = $stmt->execute();
                if ($success) {
                    Logger::info("The login log was successfully stored in database");
                }
            }
        }

        $conn->close();
    }

    public function getSpNameBySpIdentifier($identifier)
    {
        $stmt = $this->conn->prepare(
            "SELECT name " .
            "FROM " . $this->serviceProvidersMapTableName . " " .
            "WHERE identifier=?"
        );
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()["name"];
    }

    public function getIdPNameByEntityId($idpEntityId)
    {
        $stmt = $this->conn->prepare(
            "SELECT name " .
            "FROM " . $this->identityProvidersMapTableName . " " .
            "WHERE entityId=?"
        );
        $stmt->bind_param('s', $idpEntityId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()["name"];
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
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_ASSOC);
        return $r;
    }

    public function getLoginCountPerDayForService($days, $spIdentifier)
    {
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $this->statisticsTableName . " " .
                 "WHERE service=:service ";
        $params = [':service' => $spIdentifier];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_ASSOC);
        return $r;
    }

    public function getLoginCountPerDayForIdp($days, $idpIdentifier)
    {
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $this->statisticsTableName . " " .
                 "WHERE sourceIdP=:sourceIdP ";
        $params = [':sourceIdP'=>$idpIdentifier];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_ASSOC);
        return $r;
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
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_NUM);
        return $r;
    }

    public function getAccessCountForServicePerIdentityProviders($days, $spIdentifier)
    {
        $query = "SELECT IFNULL(name,sourceIdp) AS idpName, SUM(count) AS count " .
                 "FROM " . $this->identityProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $this->statisticsTableName . " ON sourceIdp = entityId ";
        $params = [':service' => $spIdentifier];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY sourceIdp, service HAVING sourceIdp != '' AND service=:service " .
                  "ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_NUM);
        return $r;
    }

    public function getAccessCountForIdentityProviderPerServiceProviders($days, $idpEntityId)
    {
        $query = "SELECT IFNULL(name,service) AS spName, SUM(count) AS count " .
                 "FROM " . $this->serviceProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $this->statisticsTableName . " ON service = identifier ";
        $params = [':sourceIdp'=>$idpEntityId];
        self::addDaysRange($days, $query, $params);
        $query .= "GROUP BY sourceIdp, service HAVING service != '' AND sourceIdp=:sourceIdp " .
                  "ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_NUM);
        return $r;
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
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_NUM);
        return $r;
    }

    private static addDaysRange($days, &$query, &$params) {
    if ($days != 0) {    // 0 = all time
        if (stripos($query, "WHERE") === false) {
            $query .= "WHERE";
        } else {
            $query .= "AND";
        }
        $query .= " CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                  "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
        $params[':days'] = $days;
    }
    }
}
