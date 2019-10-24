<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class DatabaseCommand
{

    public static function insertLogin(&$request, &$date)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $statisticsTableName = $databaseConnector->getStatisticsTableName();
        $identityProvidersMapTableName = $databaseConnector->getIdentityProvidersMapTableName();
        $serviceProvidersMapTableName = $databaseConnector->getServiceProvidersMapTableName();

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
            $stmt = $conn->prepare(
                "INSERT INTO " . $statisticsTableName . "(year, month, day, sourceIdp, service, count)" .
                " VALUES (?, ?, ?, ?, ?, '1') ON DUPLICATE KEY UPDATE count = count + 1"
            );
            $stmt->bind_param("iiiss", $year, $month, $day, $idpEntityID, $spEntityId);
            if ($stmt->execute() === false) {
                Logger::error("The login log wasn't inserted into table: " . $statisticsTableName . ".");
            }

            if (!empty($idpName)) {
                $stmt->prepare(
                    "INSERT INTO " . $identityProvidersMapTableName .
                    "(entityId, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = ?"
                );
                $stmt->bind_param("sss", $idpEntityID, $idpName, $idpName);
                $stmt->execute();
            }

            if (!empty($spName)) {
                $stmt->prepare(
                    "INSERT INTO " . $serviceProvidersMapTableName .
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

    public static function getSpNameBySpIdentifier($identifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $tableName = $databaseConnector->getServiceProvidersMapTableName();
        assert($conn != null);
        $stmt = $conn->prepare(
            "SELECT name " .
            "FROM " . $tableName . " " .
            "WHERE identifier=?"
        );
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $conn->close();
        return $result->fetch_assoc()["name"];
    }

    public static function getIdPNameByEntityId($idpEntityId)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $tableName = $databaseConnector->getIdentityProvidersMapTableName();
        assert($conn != null);
        $stmt = $conn->prepare(
            "SELECT name " .
            "FROM " . $tableName . " " .
            "WHERE entityId=?"
        );
        $stmt->bind_param('s', $idpEntityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $conn->close();
        return $result->fetch_assoc()["name"];
    }

    public static function getLoginCountPerDay($days)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $table_name . " " .
                 "WHERE service != '' ";
        $params = [];
        if ($days != 0) {    // 0 = all time
            $query .= "AND " .
                      "CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                      "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            $params[':days'] = $days;
        }
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_ASSOC);
        $conn->close();
        return $r;
    }

    public static function getLoginCountPerDayForService($days, $spIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $table_name . " " .
                 "WHERE service=:service ";
        $params = [':service' => $spIdentifier];
        if ($days != 0) {    // 0 = all time
            $query .= "AND CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                      "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            $params[':days'] = $days;
        }
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_ASSOC);
        $conn->close();
        return $r;
    }

    public static function getLoginCountPerDayForIdp($days, $idpIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $table_name . " " .
                 "WHERE sourceIdP=:sourceIdP ";
        $params = [':sourceIdP'=>$idpIdentifier];
        if ($days != 0) {    // 0 = all time
            $query .= "AND CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                      "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            $params[':days'] = $days;
        }
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_ASSOC);
        $conn->close();
        return $r;
    }

    public static function getAccessCountPerService($days)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $serviceProvidersMapTableName = $databaseConnector->getServiceProvidersMapTableName();
        $query = "SELECT IFNULL(name,service) AS spName, service, SUM(count) AS count " .
                 "FROM " . $serviceProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $table_name . " ON service = identifier ";
        $params = [];
        if ($days != 0) {    // 0 = all time
            $query .= "WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                      "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            );
            $params[':days'] = $days;
        }
        $query .= "GROUP BY service HAVING service != '' " .
                  "ORDER BY count DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_NUM);
        $conn->close();
        return $r;
    }

    public static function getAccessCountForServicePerIdentityProviders($days, $spIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $identityProvidersMapTableName = $databaseConnector->getIdentityProvidersMapTableName();
        $query = "SELECT IFNULL(name,sourceIdp) AS idpName, SUM(count) AS count " .
                 "FROM " . $identityProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $table_name . " ON sourceIdp = entityId ";
        $params = [':service' => $spIdentifier];
        if ($days != 0) {    // 0 = all time
            $query .= "WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                      "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            $params[':days'] = $days;
        }
        $query .= "GROUP BY sourceIdp, service HAVING sourceIdp != '' AND service=:service " .
                  "ORDER BY count DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_NUM);
        $conn->close();
        return $r;
    }

    public static function getAccessCountForIdentityProviderPerServiceProviders($days, $idpEntityId)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $serviceProvidersMapTableName = $databaseConnector->getServiceProvidersMapTableName();
        $query = "SELECT IFNULL(name,service) AS spName, SUM(count) AS count " .
                 "FROM " . $serviceProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $table_name . " ON service = identifier ";
        $params = [':sourceIdp'=>$idpEntityId];
        if ($days != 0) {    // 0 = all time
            $query .= "WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                      "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            $params[':days'] = $days;
        }
        $query .= "GROUP BY sourceIdp, service HAVING service != '' AND sourceIdp=:sourceIdp " .
                  "ORDER BY count DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_NUM);
        $conn->close();
        return $r;
    }

    public static function getLoginCountPerIdp($days)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $tableName = $databaseConnector->getStatisticsTableName();
        $identityProvidersMapTableName = $databaseConnector->getIdentityProvidersMapTableName();
        $query = "SELECT IFNULL(name,sourceIdp) AS idpName, sourceIdp, SUM(count) AS count " .
                 "FROM " . $identityProvidersMapTableName . " " .
                 "LEFT OUTER JOIN " . $tableName . " ON sourceIdp = entityId ";
        $params = [];
        if ($days != 0) {    // 0 = all time
            $query .= "WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                      "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            $params[':days'] = $days;
        }
        $query .= "GROUP BY sourceIdp HAVING sourceIdp != '' " .
                  "ORDER BY count DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->get_result();
        $r = $result->fetch_all(MYSQLI_NUM);
        $conn->close();
        return $r;
    }
}
