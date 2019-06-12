<?php

use SimpleSAML\Configuration;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

$config = Configuration::getInstance();
$session = Session::getSessionFromRequest();

$t = new Template($config, 'proxystatistics:spDetail-tpl.php');

$t->data['lastDays'] = filter_input(INPUT_POST, 'lastDays', FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>0]]);
$t->data['identifier'] = filter_input(INPUT_GET, 'identifier', FILTER_SANITIZE_STRING);
$t->show();
