<?php

use SimpleSAML\Module\proxystatistics\Auth\Process\DatabaseCommand;
use SimpleSAML\Module;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

const CONFIG_FILE_NAME = 'config.php';
const INSTANCE_NAME = 'instance_name';

$lastDays = $this->data['lastDays'];
$idpEntityId = $this->data['entityId'];

$this->data['jquery'] = array('core' => true, 'ui' => true, 'css' => true);
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('proxystatistics/statisticsproxy.css') . '" />';
$this->data['head'] .= '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
$this->data['head'] .= '<meta name="loginCountPerDay" id="loginCountPerDay" content="' .
    htmlspecialchars(json_encode(
        DatabaseCommand::getLoginCountPerDayForIdp($lastDays, $idpEntityId),
    JSON_NUMERIC_CHECK)) . '">';
$this->data['head'] .=
    '<meta name="accessCountForIdentityProviderPerServiceProviders" ' .
    'id="accessCountForIdentityProviderPerServiceProviders" content="' .
    htmlspecialchars(json_encode(
        DatabaseCommand::getAccessCountForIdentityProviderPerServiceProviders($lastDays, $idpEntityId),
    JSON_NUMERIC_CHECK)).'">';
$this->data['head'] .= '<meta name="translations" id="translations" content="'.htmlspecialchars(json_encode([
    'tables_identity_provider' => $this->t('{proxystatistics:Proxystatistics:templates/tables_identity_provider}'),
    'tables_service_provider' => $this->t('{proxystatistics:Proxystatistics:templates/tables_service_provider}'),
    'count' => $this->t('{proxystatistics:Proxystatistics:templates/count}'),
])).'">';

$idpName = DatabaseCommand::getIdPNameByEntityId($idpEntityId);

if (!is_null($idpName) && !empty($idpName)) {
    $this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/idpDetail_header_name}') . $idpName;
} else {
    $this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/idpDetail_header_entityId}') .
        $idpEntityId;
}

$this->includeAtTemplateBase('includes/header.php');

?>
    </head>
    <body>
    <div class="go-to-stats-btn">
        <a href="./" class="btn btn-md btn-default">
            <span class="glyphicon glyphicon-home"></span>
            <?php echo $this->t('{proxystatistics:Proxystatistics:btn_label_back_to_stats}'); ?>
        </a>
    </div>

    <?php
    require 'timeRange.include.php';
    ?>

    <h3><?php echo $this->t('{proxystatistics:Proxystatistics:templates/idpDetail_dashboard_header}'); ?></h3>

    <div class="legend">
        <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/idpDetail_dashboard_legend}'); ?></div>
    </div>

    <div id="loginsDashboard">
        <div id="line_div"></div>
        <div id="control_div"></div>
    </div>

    <h3><?php echo $this->t('{proxystatistics:Proxystatistics:templates/idpDetail_graph_header}'); ?></h3>
    <div class="legend">
        <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/idpDetail_graph_legend}'); ?></div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div id="accessedSpsChartDetail" class="pieChart"></div>
        </div>
        <div class="col-md-4">
            <div id="accessedSpsTable" class="table"></div>
        </div>
    </div>
    </body>
<?php
$this->data['htmlinject']['htmlContentPost'][]
    = '<script type="text/javascript" src="' . Module::getMOduleUrl('proxystatistics/index.js') . '"></script>';
$this->includeAtTemplateBase('includes/footer.php');
