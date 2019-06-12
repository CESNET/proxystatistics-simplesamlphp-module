<?php

use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Logger;
use SimpleSAML\Module\proxystatistics\Auth\Process\DatabaseCommand;

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

$this->data['jquery'] = array('core' => true, 'ui' => true, 'css' => true);
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('proxystatistics/statisticsproxy.css') . '" />';
$this->data['head'] .= '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';

if (!isset($this->data['lastDays'])) {
    $this->data['lastDays'] = 0;
}

if (!isset($this->data['tab'])) {
    $this->data['tab'] = 1;
}
$this->data['head'] .= '<meta name="loginCountPerDay" id="loginCountPerDay" content="' .
    htmlspecialchars(json_encode(DatabaseCommand::getLoginCountPerDay($this->data['lastDays']), JSON_NUMERIC_CHECK))
    . '">';
$this->data['head'] .= '<meta name="loginCountPerIdp" id="loginCountPerIdp" content="' .
    htmlspecialchars(json_encode(DatabaseCommand::getLoginCountPerIdp($this->data['lastDays']), JSON_NUMERIC_CHECK))
    . '">';
$this->data['head'] .= '<meta name="accessCountPerService" id="accessCountPerService" content="' .
    htmlspecialchars(json_encode(
        DatabaseCommand::getAccessCountPerService($this->data['lastDays']),
    JSON_NUMERIC_CHECK)) . '">';
$this->data['head'] .= '<meta name="translations" id="translations" content="'.htmlspecialchars(json_encode([
    'tables_identity_provider' => $this->t('{proxystatistics:Proxystatistics:templates/tables_identity_provider}'),
    'tables_service_provider' => $this->t('{proxystatistics:Proxystatistics:templates/tables_service_provider}'),
    'count' => $this->t('{proxystatistics:Proxystatistics:templates/count}'),
])).'">';
$this->includeAtTemplateBase('includes/header.php');
?>

<div id="tabdiv" data-activetab="<?php echo htmlspecialchars($this->data['tab']);?>">
    <ul class="tabset_tabs" width="100px">
        <li><a id="tab-1"
               href='<?php echo "summary.php?lastDays=" . $this->data['lastDays']; ?>'>
                <?php echo $this->t('{proxystatistics:Proxystatistics:summary}'); ?></a>
        </li>
        <li><a id="tab-2"
               href='<?php echo "identityProviders.php?lastDays=" . $this->data['lastDays']; ?>'>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_idpsDetail}'); ?></a>
        </li>
        <li><a id="tab-3"
               href='<?php echo "serviceProviders.php?lastDays=" . $this->data['lastDays']; ?>'>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_spsDetail}'); ?></a>
        </li>
    </ul>
</div>

<?php
$this->data['htmlinject']['htmlContentPost'][]
    = '<script type="text/javascript" src="' . Module::getMOduleUrl('proxystatistics/index.js') . '"></script>';
$this->includeAtTemplateBase('includes/footer.php');
?>
