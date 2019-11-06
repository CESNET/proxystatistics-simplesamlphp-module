<?php

use SimpleSAML\Configuration;
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
    $attributes = $as->getAttributes();
    $authAttributes = $configStatisticsproxy->getArray('requireAuth.attributes', []);
    if ($authAttributes) {
        foreach ($authAttributes as $attribute => $values) {
            if (!isset($attributes[$attribute])) {
                throw new \SimpleSAML\Error\Error('NOACCESS');
            }
            $currentValues = $attributes[$attribute];
            if (
                is_array($values[0]) && !array_reduce($values, function ($res, $value) use ($currentValues) {
                    return $res || empty(array_diff($value, $currentValues));
                }, false)
            ) {
                throw new \SimpleSAML\Error\Error('NOACCESS');
            }
            if (!is_array($values[0]) && empty(array_intersect($currentValues, $values))) {
                throw new \SimpleSAML\Error\Error('NOACCESS');
            }
        }
    }
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

if ($mode === 'IDP') {
    $t->data['tabsAttributes'] = [
        'PROXY' => 'id="tab-1" href="summary.php?lastDays=' . $lastDays . '"',
        'IDP' => 'class="hidden" id="tab-2" href="identityProviders.php?lastDays=' . $lastDays . '"',
        'SP' => 'id="tab-3" href="serviceProviders.php?lastDays=' . $lastDays . '"',
    ];
} elseif ($mode === 'SP') {
    $t->data['tabsAttributes'] = [
        'PROXY' => 'id="tab-1" href="summary.php?lastDays=' . $lastDays . '"',
        'IDP' => 'id="tab-2" href="identityProviders.php?lastDays=' . $lastDays . '"',
        'SP' => 'class="hidden" id="tab-3" href="serviceProviders.php?lastDays=' . $lastDays . '"',
    ];
} elseif ($mode === 'PROXY') {
    $t->data['tabsAttributes'] = [
        'PROXY' => 'id="tab-1" href="summary.php?lastDays=' . $lastDays . '"',
        'IDP' => 'id="tab-2" href="identityProviders.php?lastDays=' . $lastDays . '"',
        'SP' => 'id="tab-3" href="serviceProviders.php?lastDays=' . $lastDays . '"',
    ];
}

$t->show();
