<?php

declare(strict_types=1);

use SimpleSAML\Module\proxystatistics\Config;
use SimpleSAML\Module\proxystatistics\Templates;

Templates::showProviders(Config::MODE_IDP, 1);
