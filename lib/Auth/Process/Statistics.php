<?php

declare(strict_types=1);

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use DateTime;
use Exception;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Logger;
use SimpleSAML\Module\proxystatistics\DatabaseCommand;

class Statistics extends ProcessingFilter
{
    private const STAGE = 'proxystatistics:Statistics';

    private const DEBUG_PREFIX = self::STAGE . ' - ';

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
    }

    public function process(&$request)
    {
        $dateTime = new DateTime();
        $dbCmd = new DatabaseCommand();
        try {
            $dbCmd->insertLogin($request, $dateTime);
        } catch (Exception $ex) {
            Logger::error(
                self::DEBUG_PREFIX . 'Caught exception while inserting login into statistics: ' . $ex->getMessage()
            );
        }
    }
}
