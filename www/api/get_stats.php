<?php
// required headers
use SimpleSAML\Module\proxystatistics\DatabaseCommand;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Content-Encoding: gzip");

$year = $_GET['year'] ?? date("Y");

$dbCmd = new DatabaseCommand();
$data = $dbCmd->get_api_stats($year);

echo gzencode(json_encode($data));

