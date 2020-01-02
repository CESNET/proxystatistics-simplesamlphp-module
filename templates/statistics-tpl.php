<?php

use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Logger;
use SimpleSAML\Module\proxystatistics\DatabaseCommand;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */

const CONFIG_FILE_NAME = 'config.php';
const INSTANCE_NAME = 'instance_name';

$config = Configuration::getConfig(CONFIG_FILE_NAME);
$instanceName = $config->getString(INSTANCE_NAME, null);
if (!is_null($instanceName)) {
    $this->data['header'] = $instanceName . ' ' .
        $this->t('{proxystatistics:Proxystatistics:templates/statistics_header}');
} else {
    $this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/statistics_header}');
    Logger::warning('Missing configuration: config.php - instance_name is not set.');
}

require_once 'charts.include.php';

if (!isset($this->data['lastDays'])) {
    $this->data['lastDays'] = 0;
}

if (!isset($this->data['tab'])) {
    $this->data['tab'] = 1;
}
$dbCmd = new DatabaseCommand();
$this->data['head'] .= '<meta name="loginCountPerDay" id="loginCountPerDay" content="' .
    htmlspecialchars(json_encode($dbCmd->getLoginCountPerDay($this->data['lastDays']), JSON_NUMERIC_CHECK))
    . '">';
$this->data['head'] .= '<meta name="loginCountPerIdp" id="loginCountPerIdp" content="' .
    htmlspecialchars(json_encode($dbCmd->getLoginCountPerIdp($this->data['lastDays']), JSON_NUMERIC_CHECK))
    . '">';
$this->data['head'] .= '<meta name="accessCountPerService" id="accessCountPerService" content="' .
    htmlspecialchars(json_encode($dbCmd->getAccessCountPerService($this->data['lastDays']), JSON_NUMERIC_CHECK))
    . '">';
$this->data['head'] .= '<meta name="translations" id="translations" content="'.htmlspecialchars(json_encode([
    'tables_identity_provider' => $this->t('{proxystatistics:Proxystatistics:templates/tables_identity_provider}'),
    'tables_service_provider' => $this->t('{proxystatistics:Proxystatistics:templates/tables_service_provider}'),
    'count' => $this->t('{proxystatistics:Proxystatistics:templates/count}'),
    'other' => $this->t('{proxystatistics:Proxystatistics:templates/other}'),
])).'">';
$this->includeAtTemplateBase('includes/header.php');
?>

<div id="tabdiv" data-activetab="<?php echo htmlspecialchars($this->data['tab']);?>">
    <ul class="tabset_tabs" width="100px">
        <li>
            <a <?php echo $this->data['tabsAttributes']['PROXY'] ?>>
                <?php echo $this->t('{proxystatistics:Proxystatistics:summary}'); ?>
            </a>
        </li>
        <li>
            <a <?php echo $this->data['tabsAttributes']['IDP'] ?>>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_idpsDetail}'); ?>
            </a>
        </li>
        <li>
            <a <?php echo $this->data['tabsAttributes']['SP'] ?>>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_spsDetail}'); ?>
            </a>
        </li>
    </ul>
</div>

<?php
$this->data['htmlinject']['htmlContentPost'][]
    = '<script type="text/javascript" src="' . Module::getMOduleUrl('proxystatistics/index.js') . '"></script>';
$this->includeAtTemplateBase('includes/footer.php');
?>
