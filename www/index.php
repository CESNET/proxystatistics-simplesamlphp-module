<?php

use SimpleSAML\Configuration;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

$config = Configuration::getInstance();
$session = Session::getSessionFromRequest();

$t = new Template($config, 'proxystatistics:statistics-tpl.php');
$t->data['lastDays'] = filter_input(
    INPUT_POST,
    'lastDays',
    FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>0]]
);
$t->data['tab'] = filter_input(
    INPUT_POST,
    'tab',
    FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>1]]
);
$t->show();
