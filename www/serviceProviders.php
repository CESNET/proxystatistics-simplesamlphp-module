<?php
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();

$t = new SimpleSAML_XHTML_Template($config, 'proxystatistics:serviceProviders-tpl.php');
$t->data['lastDays'] = $_GET['lastDays'];
$t->show();
