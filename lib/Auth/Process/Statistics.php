<?php

declare(strict_types=1);

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use DateTime;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Logger;
use SimpleSAML\Module\proxystatistics\DatabaseCommand;

class Statistics extends ProcessingFilter
{
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
        } catch (\Exception $ex) {
            Logger::error('Caught exception while inserting login into statistics: ' . $ex->getMessage());
        }
    }
}
