<?php
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();

$t = new SimpleSAML_XHTML_Template($config, 'proxystatistics:spDetail-tpl.php');

if (!isset($_POST['lastDays'])) {
    $_POST['lastDays'] = 0;
}
$t->data['lastDays'] = $_POST['lastDays'];
$t->data['identifier'] = $_GET['identifier'];
$t->show();
