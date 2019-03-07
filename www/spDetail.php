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

if (!isset($_POST['lastDays'])) {
    $_POST['lastDays'] = 0;
}

$t->data['lastDays'] = $_POST['lastDays'];
$t->data['identifier'] = $_GET['identifier'];
$t->show();
