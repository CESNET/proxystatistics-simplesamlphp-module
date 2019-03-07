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

if (!isset($_POST['lastDays'])) {
    $_POST['lastDays'] = 0;
}

if (!isset($_POST['tab'])) {
    $_POST['tab'] = 1;
}

$t->data['lastDays'] = $_POST['lastDays'];
$t->data['tab'] = $_POST['tab'];
$t->show();
