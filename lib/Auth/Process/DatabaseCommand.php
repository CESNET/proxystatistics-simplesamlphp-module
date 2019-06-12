<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

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
        $idpEntityID = $request['saml:sp:IdP'];
        $idpName = $request['Attributes']['sourceIdPName'][0];
        $spEntityId = $request['Destination']['entityid'];
        $spName = $request['Destination']['name']['en'];
        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');

        if (is_null($idpEntityID) || empty($idpEntityID) || is_null($spEntityId) || empty($spEntityId)) {
            Logger::error(
                "Some from attribute: 'idpEntityId', 'idpName', 'spEntityId' and 'spName'" .
                " is null or empty and login log wasn't inserted into the database."
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

            if (!is_null($idpName) && !empty($idpName)) {
                $stmt->prepare(
                    "INSERT INTO " . $identityProvidersMapTableName .
                    "(entityId, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = ?"
                );
                $stmt->bind_param("sss", $idpEntityID, $idpName, $idpName);
                $stmt->execute();
            }

            if (!is_null($spName) && !empty($spName)) {
                $stmt->prepare(
                    "INSERT INTO " . $serviceProvidersMapTableName .
                    "(identifier, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = ?"
                );
                $stmt->bind_param("sss", $spEntityId, $spName, $spName);
                $stmt->execute();
            }
        }

        Logger::info("The login log was successfully stored in database");

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
        if ($days == 0) {    // 0 = all time
            $stmt = $conn->prepare(
                "SELECT year, month, day, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "WHERE service != '' " .
                "GROUP BY year DESC,month DESC,day DESC"
            );
        } else {
            $stmt = $conn->prepare(
                "SELECT year, month, day, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "WHERE service != '' AND " .
                "CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                "BETWEEN CURDATE() - INTERVAL ? DAY AND CURDATE()" .
                "GROUP BY year DESC,month DESC,day DESC"
            );
            $stmt->bind_param('d', $days);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            echo "[new Date(" .
                $row["year"] . "," .
                ($row["month"] - 1) .", " .
                $row["day"] . "), {v:" .
                $row["count"] .
                "}],";
        }
        $conn->close();
    }

    public static function getLoginCountPerDayForService($days, $spIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        if ($days == 0) {    // 0 = all time
            $stmt = $conn->prepare(
                "SELECT year, month, day, SUM(count) AS count " .
                    "FROM " . $table_name . " " .
                    "WHERE service=? " .
                    "GROUP BY year DESC,month DESC,day DESC"
            );
            $stmt->bind_param('s', $spIdentifier);
        } else {
            $stmt = $conn->prepare(
                "SELECT year, month, day, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "WHERE service=? " .
                "AND CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                "BETWEEN CURDATE() - INTERVAL ? DAY AND CURDATE() " .
                "GROUP BY year DESC,month DESC,day DESC"
            );
            $stmt->bind_param('sd', $spIdentifier, $days);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            echo "[new Date(" .
                $row["year"] . "," .
                ($row["month"] - 1) . ", " .
                $row["day"] . "), {v:" .
                $row["count"] .
                "}],";
        }
        $conn->close();
    }

    public static function getLoginCountPerDayForIdp($days, $idpIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        if ($days == 0) {    // 0 = all time
            $stmt = $conn->prepare(
                "SELECT year, month, day, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "WHERE sourceIdP=? " .
                "GROUP BY year DESC,month DESC,day DESC"
            );
            $stmt->bind_param('s', $idpIdentifier);
        } else {
            $stmt = $conn->prepare(
                "SELECT year, month, day, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "WHERE sourceIdP=? " .
                "AND CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                "BETWEEN CURDATE() - INTERVAL ? DAY AND CURDATE() " .
                "GROUP BY year DESC,month DESC,day DESC"
            );
            $stmt->bind_param('sd', $idpIdentifier, $days);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            echo "[new Date(" .
                $row["year"] . "," .
                ($row["month"] - 1) . ", " .
                $row["day"] . "), {v:" .
                $row["count"] .
                "}],";
        }
        $conn->close();
    }

    public static function getAccessCountPerService($days)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $serviceProvidersMapTableName = $databaseConnector->getServiceProvidersMapTableName();
        if ($days == 0) {    // 0 = all time
            $stmt = $conn->prepare(
                "SELECT service, IFNULL(name,service) AS spName, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "LEFT OUTER JOIN " . $serviceProvidersMapTableName . " ON service = identifier " .
                "GROUP BY service HAVING service != '' " .
                "ORDER BY count DESC"
            );
        } else {
            $stmt = $conn->prepare(
                "SELECT year, month, day, service, IFNULL(name,service) AS spName, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "LEFT OUTER JOIN " . $serviceProvidersMapTableName . "  ON service = identifier " .
                "WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                "BETWEEN CURDATE() - INTERVAL ? DAY AND CURDATE() " .
                "GROUP BY service HAVING service != '' " .
                "ORDER BY count DESC"
            );
            $stmt->bind_param('d', $days);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            echo "['" .
                str_replace("'", "\'", $row["spName"]) .
                "', '" . $row ["service"] . "', " . $row["count"] . "],";
        }
        $conn->close();
    }

    public static function getAccessCountForServicePerIdentityProviders($days, $spIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $identityProvidersMapTableName = $databaseConnector->getIdentityProvidersMapTableName();
        if ($days == 0) {    // 0 = all time
            $stmt = $conn->prepare(
                "SELECT sourceIdp, service, IFNULL(name,sourceIdp) AS idpName, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "LEFT OUTER JOIN " . $identityProvidersMapTableName . " ON sourceIdp = entityId " .
                "GROUP BY sourceIdp, service HAVING sourceIdp != '' AND service=? " .
                "ORDER BY count DESC"
            );
            $stmt->bind_param('s', $spIdentifier);
        } else {
            $stmt = $conn->prepare(
                "SELECT year, month, day, sourceIdp, service, IFNULL(name,sourceIdp) AS idpName, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "LEFT OUTER JOIN " . $identityProvidersMapTableName . "  ON sourceIdp = entityId " .
                "WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                "BETWEEN CURDATE() - INTERVAL ? DAY AND CURDATE() " .
                "GROUP BY sourceIdp, service HAVING sourceIdp != '' AND service=? " .
                "ORDER BY count DESC"
            );
            $stmt->bind_param('ds', $days, $spIdentifier);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            echo "['" . str_replace("'", "\'", $row["idpName"]) . "', " . $row["count"] . "],";
        }
        $conn->close();
    }

    public static function getAccessCountForIdentityProviderPerServiceProviders($days, $idpEntityId)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $table_name = $databaseConnector->getStatisticsTableName();
        $serviceProvidersMapTableName = $databaseConnector->getServiceProvidersMapTableName();
        if ($days == 0) {    // 0 = all time
            $stmt = $conn->prepare(
                "SELECT sourceIdp, service, IFNULL(name,service) AS spName, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "LEFT OUTER JOIN " . $serviceProvidersMapTableName . " ON service = identifier " .
                "GROUP BY sourceIdp, service HAVING service != '' AND sourceIdp=? " .
                "ORDER BY count DESC"
            );
            $stmt->bind_param('s', $idpEntityId);
        } else {
            $stmt = $conn->prepare(
                "SELECT year, month, day, sourceIdp, service, IFNULL(name,service) AS spName, SUM(count) AS count " .
                "FROM " . $table_name . " " .
                "LEFT OUTER JOIN " . $serviceProvidersMapTableName . "  ON service = identifier " .
                "WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                "BETWEEN CURDATE() - INTERVAL ? DAY AND CURDATE() " .
                "GROUP BY sourceIdp, service HAVING service != '' AND sourceIdp=? " .
                "ORDER BY count DESC"
            );
            $stmt->bind_param('ds', $days, $idpEntityId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            echo "['" . str_replace("'", "\'", $row["spName"]) . "', " . $row["count"] . "],";
        }
        $conn->close();
    }

    public static function getLoginCountPerIdp($days)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != null);
        $tableName = $databaseConnector->getStatisticsTableName();
        $identityProvidersMapTableName = $databaseConnector->getIdentityProvidersMapTableName();
        if ($days == 0) {    // 0 = all time
            $stmt = $conn->prepare(
                "SELECT sourceIdp, IFNULL(name,sourceIdp) AS idpName, SUM(count) AS count " .
                "FROM " . $tableName . " " .
                "LEFT OUTER JOIN " . $identityProvidersMapTableName . " ON sourceIdp = entityId " .
                "GROUP BY sourceIdp HAVING sourceIdp != '' " .
                "ORDER BY count DESC"
            );
        } else {
            $stmt = $conn->prepare(
                "SELECT year, month, day, sourceIdp, IFNULL(name,sourceIdp) AS idpName, SUM(count) AS count " .
                "FROM " . $tableName . " " .
                "LEFT OUTER JOIN " . $identityProvidersMapTableName . " ON sourceIdp = entityId " .
                "WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) " .
                "BETWEEN CURDATE() - INTERVAL ? DAY AND CURDATE() " .
                "GROUP BY sourceIdp HAVING sourceIdp != '' " .
                "ORDER BY count DESC"
            );
            $stmt->bind_param('d', $days);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            echo "['" .
                str_replace("'", "\'", $row["idpName"]) .
                "', '" . $row['sourceIdp'] . "', " . $row["count"] . "],";
        }
        $conn->close();
    }
}
