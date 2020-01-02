<?php

use SimpleSAML\Module\proxystatistics\DatabaseCommand;
use SimpleSAML\Module;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

const CONFIG_FILE_NAME = 'config.php';
const INSTANCE_NAME = 'instance_name';

$lastDays = $this->data['lastDays'];
$spIdentifier = $this->data['identifier'];

require_once 'charts.include.php';
require_once 'functions.include.php';

$dbCmd = new DatabaseCommand();
$this->data['head'] .= '<meta name="loginCountPerDay" id="loginCountPerDay" content="' .
    htmlspecialchars(json_encode($dbCmd->getLoginCountPerDayForService($lastDays, $spIdentifier), JSON_NUMERIC_CHECK))
    . '">';
$this->data['head'] .=
    '<meta name="accessCountForServicePerIdentityProviders" id="accessCountForServicePerIdentityProviders" content="' .
    htmlspecialchars(json_encode(
        $dbCmd->getAccessCountForServicePerIdentityProviders($lastDays, $spIdentifier),
        JSON_NUMERIC_CHECK
    )) . '">';
$this->data['head'] .= '<meta name="translations" id="translations" content="'.htmlspecialchars(json_encode([
    'tables_identity_provider' => $this->t('{proxystatistics:Proxystatistics:templates/tables_identity_provider}'),
    'tables_service_provider' => $this->t('{proxystatistics:Proxystatistics:templates/tables_service_provider}'),
    'count' => $this->t('{proxystatistics:Proxystatistics:templates/count}'),
])).'">';

$spName = $dbCmd->getSpNameBySpIdentifier($spIdentifier);

if (!empty($spName)) {
    $this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/spDetail_header_name}') .
        $spName;
} else {
    $this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/spDetail_header_identifier}') .
        $spIdentifier;
}

$this->includeAtTemplateBase('includes/header.php');

?>

    </head>
    <body>
    <div class="go-to-stats-btn">
        <a href="./" class="btn btn-md btn-default"><span class="glyphicon glyphicon-home"></span>
            <?php echo $this->t('{proxystatistics:Proxystatistics:btn_label_back_to_stats}'); ?>
        </a>
    </div>

    <?php require_once 'timeRange.include.php'; ?>

    <h3><?php echo $this->t('{proxystatistics:Proxystatistics:templates/spDetail_dashboard_header}'); ?></h3>

    <div class="legend">
        <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/spDetail_dashboard_legend}'); ?></div>
    </div>

    <?php require_once 'loginsDashboard.include.php'; ?>

    <div class="<?php echo $this->data['spDetailGraphClass'] ?>">
        <h3><?php echo $this->t('{proxystatistics:Proxystatistics:templates/spDetail_graph_header}'); ?></h3>
        <div class="legend">
            <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/spDetail_graph_legend}'); ?></div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <?php pieChart('usedIdPsChartDetail'); ?>
            </div>
            <div class="col-md-4">
                <div id="usedIdPsTable" class="table-container"></div>
            </div>
        </div>
    </div>
    </body>
<?php
$this->data['htmlinject']['htmlContentPost'][]
    = '<script type="text/javascript" src="' . Module::getMOduleUrl('proxystatistics/index.js') . '"></script>';
$this->includeAtTemplateBase('includes/footer.php');
