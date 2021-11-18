<?php

declare(strict_types=1);

use SimpleSAML\Module\proxystatistics\Config;
use SimpleSAML\Module\proxystatistics\Templates;

if (empty($_GET['side']) || ! in_array($_GET['side'], Config::SIDES, true)) {
    throw new \Exception('Invalid argument');
}
Templates::showDetail($_GET['side']);
