<?php

use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

const CONFIG_FILE_NAME_STATISTICSPROXY = 'module_statisticsproxy.php';
const MODE = 'mode';

$config = Configuration::getInstance();
$session = Session::getSessionFromRequest();

$configStatisticsproxy = Configuration::getConfig(CONFIG_FILE_NAME_STATISTICSPROXY);

$authSource = $configStatisticsproxy->getString('requireAuth.source', '');
if ($authSource) {
    $as = new \SimpleSAML\Auth\Simple($authSource);
    $as->requireAuth();
}

$mode = $configStatisticsproxy->getString(MODE, 'PROXY');

$t = new Template($config, 'proxystatistics:statistics-tpl.php');

$lastDays = filter_input(
    INPUT_POST,
    'lastDays',
    FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>0]]
);

$t->data['lastDays'] = $lastDays;

$t->data['tab'] = filter_input(
    INPUT_POST,
    'tab',
    FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>1]]
);

$t->data['tabsAttributes'] = [];
$tabs = [
    1 => ['tag' => 'PROXY', 'page' => 'summary.php', 'hidden' => false],
    ['tag' => 'IDP', 'page' => 'identityProviders.php', 'hidden' => $mode === 'IDP'],
    ['tag' => 'SP', 'page' => 'serviceProviders.php', 'hidden' => $mode === 'SP'],
];
foreach ($tabs as $i => $tab) {
    $t->data['tabsAttributes'][$tab['tag']] = sprintf(
        '%sid="tab-%d" href="%s?lastDays=%d"',
        $tab['hidden'] ? 'class="hidden" ' : '',
        $i,
        Module::getModuleURL('proxystatistics/' . $tab['page']),
        $lastDays
    );
}

$t->show();
