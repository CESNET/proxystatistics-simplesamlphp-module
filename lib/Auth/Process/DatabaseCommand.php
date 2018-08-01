<?php
include ("DatabaseConnector.php");
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

class DatabaseCommand
{

    public static function insertLogin(&$request, &$date)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $identityProvidersTableName = $databaseConnector->getIdentityProvidersTableName();
        $serviceProvidersTableName = $databaseConnector->getServiceProvidersTableName();
        $sourceIdp = $request['saml:sp:IdP'];
        $service = $request['Destination']['name']['en'];

        $sql = "INSERT INTO ".$identityProvidersTableName."(year, month, day, sourceIdp, count) VALUES ('".$date->format('Y')."','".$date->format('m')  ."','".$date->format('d')."','".$sourceIdp."','1') ON DUPLICATE KEY UPDATE count = count + 1";
        SimpleSAML\Logger::info($sql);
        if ($conn->query($sql) === FALSE) {
            SimpleSAML\Logger::error("The login log wasn't inserted into the database.");
        }

        $sql = "INSERT INTO ".$serviceProvidersTableName."(year, month, day, service, count) VALUES ('".$date->format('Y')."','".$date->format('m')  ."','".$date->format('d')."','".$service."','1') ON DUPLICATE KEY UPDATE count = count + 1";
        SimpleSAML\Logger::info($sql);
        if ($conn->query($sql) === FALSE) {
            SimpleSAML\Logger::error("The login log wasn't inserted into the database.");
        }

        $conn->close();
    }

    public static function getLoginCountPerDay()
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getIdentityProvidersTableName();
        $sql = "SELECT year, month, day, SUM(count) AS count FROM ".$table_name." GROUP BY year,month,day";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            echo "[new Date(".$row["year"].",". ($row["month"] - 1 ). ", ".$row["day"]."), {v:".$row["count"]."}],";
        }
        $conn->close();
    }


    public static function getLoginCountPerDeyPerService()
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getIdentityProvidersTableName();
        $sql = "SELECT year, month, sourceIdp, SUM(count) AS count FROM ".$table_name. " GROUP BY year, month, sourceIdp HAVING sourceIdp != ''";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            echo "[new Date(".$row["year"].",".($row["month"] - 1 )."),'".$row["sourceIdp"]."', {v:".$row["count"]."}],";
        }
        $conn->close();
    }

    public static function getAccessToServicesPerMonth()
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getServiceProvidersTableName();
        $sql = "SELECT year, month, service, SUM(count) AS count FROM ".$table_name." GROUP BY year, month, service HAVING service != ''";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            echo "[new Date(".$row["year"].",".($row["month"] - 1 )."),'".$row["service"]."', {v:".$row["count"]."}],";        }
        $conn->close();
    }

    public static function getCountOfAllLogins()
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getIdentityProvidersTableName();
        $sql = "SELECT SUM(count) AS count FROM " . $table_name;
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $count = $row["count"];
        }
        $conn->close();
        if ($count === null)
        {
            $count = 0;
        }
        echo $count;
    }

    public static function getCountOfAllLoginsForToday()
    {
        $count = 0;
        $dateTime = new DateTime();
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getIdentityProvidersTableName();
        $sql = "SELECT SUM(count) AS count FROM " . $table_name." WHERE year = ".$dateTime->format('Y')." AND month=".$dateTime->format('m')." AND day = ".$dateTime->format('d');
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $count = $row["count"];
        }
        $conn->close();
        if ($count === null)
        {
            $count = 0;
        }
        echo $count;
    }


    public static function getAccessCountPerService()
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getServiceProvidersTableName();
        $sql = "SELECT service, SUM(count) AS count FROM ".$table_name." GROUP BY service HAVING service != ''";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            echo "['".$row["service"]."', ".$row["count"]."],";
        }
        $conn->close();
    }

    public static function getLoginCountPerIdp()
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getIdentityProvidersTableName();
        $sql = "SELECT sourceIdp, SUM(count) AS count FROM ".$table_name." GROUP BY sourceIdp HAVING sourceIdp != ''";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            echo "['".$row["sourceIdp"]."', ".$row["count"]."],";
        }
        $conn->close();
    }

    public static function getCountOfUsedIdp()
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getIdentityProvidersTableName();
        $sql = "SELECT COUNT(*) AS count FROM (SELECT DISTINCT sourceIdp FROM ".$table_name." ) AS idps WHERE sourceIdp != ''";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            $count = $row["count"];
        }
        $conn->close();
        if ($count === null)
        {
            $count = 0;
        }
        echo $count;
    }

    public static function getCountOfAccesedServices()
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $table_name = $databaseConnector->getServiceProvidersTableName();
        $sql = "SELECT COUNT(*) AS count FROM (SELECT DISTINCT service FROM ".$table_name." ) AS services WHERE service != ''";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            $count = $row["count"];
        }
        $conn->close();
        if ($count === null)
        {
            $count = 0;
        }
        echo $count;
    }

	public static function getAverageLoginCountPerDay()
	{
		$databaseConnector = new DatabaseConnector();
		$conn = $databaseConnector->getConnection();
		assert($conn != NULL);
		$table_name = $databaseConnector->getServiceProvidersTableName();
		$sql = "SELECT AVG(count) as avg_count FROM (SELECT year, month, day, SUM(count) AS count FROM " . $table_name .  " GROUP BY year,month,day ) AS average_count;";
		$result = $conn->query($sql);
		while($row = $result->fetch_assoc()) {
			$avg_count = $row["avg_count"];
		}
		$conn->close();
		if ($avg_count === null)
		{
			$avg_count = 0;
		}
		echo round($avg_count);
	}

	public static function getMaxLoginCountPerDay()
	{
		$databaseConnector = new DatabaseConnector();
		$conn = $databaseConnector->getConnection();
		assert($conn != NULL);
		$table_name = $databaseConnector->getServiceProvidersTableName();
		$sql = "SELECT MAX(count) as max_count FROM (SELECT year, month, day, SUM(count) AS count FROM " . $table_name .  " GROUP BY year,month,day ) AS maximal_count;";
		$result = $conn->query($sql);
		while($row = $result->fetch_assoc()) {
			$max_count = $row["max_count"];
		}
		$conn->close();
		if ($max_count === null)
		{
			$max_count = 0;
		}
		echo $max_count;
	}
}