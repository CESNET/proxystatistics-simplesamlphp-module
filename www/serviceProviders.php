<?php

/**
 * @author Pavel Břoušek <brousek@ics.muni.cz>
 */

use SimpleSAML\Module\proxystatistics\Config;
use SimpleSAML\Module\proxystatistics\Templates;

Templates::showProviders(Config::MODE_SP, 2);
