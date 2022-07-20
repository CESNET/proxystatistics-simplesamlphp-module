<?php

declare(strict_types=1);

use SimpleSAML\Logger;
use SimpleSAML\Module\proxystatistics\Config;
use SimpleSAML\Module\proxystatistics\DatabaseCommand;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Logger::info(
        'proxystatistics:writeLoginApi - API write called not using POST nor PUT, returning 405 response code'
    );
    header('HTTP/1.0 405 Method Not Allowed');
    exit;
}
$config = Config::getInstance();

if (!$config->isApiWriteEnabled()) {
    Logger::info(
        'proxystatistics:writeLoginApi - API write called, but disabled in config. Returning 501 response code'
    );
    header('HTTP/1.0 501 Not Implemented');
    exit;
}

$authUsername = $_SERVER['PHP_AUTH_USER'] ?? '';
$authPass = $_SERVER['PHP_AUTH_PW'] ?? '';

$username = $config->getApiWriteUsername();
$passwordHash = $config->getApiWritePasswordHash();

// If we get here, username was provided. Check password.
if ($authUsername !== $username || !password_verify($authPass, $passwordHash)) {
    Logger::info(
        'proxystatistics:writeLoginApi - API write called with bad credentials (' . $authUsername . ':' . $authPass . ') returning 401 response code'
    );
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true, 5, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

if (!empty(array_diff(
    [
        DatabaseCommand::API_USER_ID, DatabaseCommand::API_SERVICE_IDENTIFIER, DatabaseCommand::API_SERVICE_NAME, DatabaseCommand::API_IDP_IDENTIFIER, DatabaseCommand::API_IDP_NAME, ],
    array_keys($data)
))) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

$dateTime = new DateTime();
$dbCmd = new DatabaseCommand();
try {
    $dbCmd->insertLoginFromApi($data, $dateTime);
} catch (Exception $ex) {
    Logger::error(
        'proxystatistics:writeLoginApi - Caught exception while inserting login into statistics: ' . $ex->getMessage()
    );
}
