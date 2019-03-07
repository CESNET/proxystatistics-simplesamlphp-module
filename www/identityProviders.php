<?php

use SimpleSAML\Configuration;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

$config = Configuration::getInstance();
$session = Session::getSessionFromRequest();

$t = new Template($config, 'proxystatistics:identityProviders-tpl.php');
$t->data['lastDays'] = $_GET['lastDays'];
$t->show();
