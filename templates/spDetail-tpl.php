<?php

use SimpleSAML\Module\proxystatistics\Auth\Process\DatabaseCommand;
use SimpleSAML\Module;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

const CONFIG_FILE_NAME = 'config.php';
const INSTANCE_NAME = 'instance_name';


$this->data['jquery'] = array('core' => true, 'ui' => true, 'css' => true);
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('proxystatistics/statisticsproxy.css') . '" />';
$this->data['head'] .= '';
$this->data['head'] .= '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
$this->data['head'] .= '<script type="text/javascript">
$(document).ready(function() {
	$("#tabdiv").tabs();
});
</script>';

$lastDays = $this->data['lastDays'];

$spIdentifier = $this->data['identifier'];


$spName = DatabaseCommand::getSpNameBySpIdentifier($spIdentifier);

if (!is_null($spName) && !empty($spName)) {
    $this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/spDetail_header_name}') .
        $spName;
} else {
    $this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/spDetail_header_identifier}') .
        $spIdentifier;
}

$this->includeAtTemplateBase('includes/header.php');

?>

    <script type="text/javascript">
        google.charts.load('current', {'packages': ['corechart', 'controls', 'table']});
        google.charts.setOnLoadCallback(drawLoginsChart);
        google.charts.setOnLoadCallback(drawUsedIdpsChart);
        google.charts.setOnLoadCallback(drawUsedIdpsTable);

        function drawLoginsChart() {
            var data = google.visualization.arrayToDataTable([
                ['Date', 'Count'],
                <?php DatabaseCommand::getLoginCountPerDayForService($lastDays, $spIdentifier)?>
            ]);

            var dashboard = new google.visualization.Dashboard(document.getElementById('loginsDashboard'));

            var chartRangeFilter = new google.visualization.ControlWrapper({
                'controlType': 'ChartRangeFilter',
                'containerId': 'control_div',
                'options': {
                    'filterColumnLabel': 'Date'
                }
            });
            var chart = new google.visualization.ChartWrapper({
                'chartType': 'LineChart',
                'containerId': 'line_div',
                'options': {
                    'legend': 'none'
                }
            });
            dashboard.bind(chartRangeFilter, chart);
            dashboard.draw(data);
        }

        function drawUsedIdpsChart() {
            var data = google.visualization.arrayToDataTable([
                ['service', 'Count'],
                <?php DatabaseCommand::getAccessCountForServicePerIdentityProviders($lastDays, $spIdentifier)?>
            ]);

            var options = {
                pieSliceText: 'value',
                chartArea: {left: 20, top: 0, width: '100%', height: '100%'}
            };

            var chart = new google.visualization.PieChart(document.getElementById('usedIdPsChartDetail'));

            data.sort([{column: 1, desc: true}]);
            chart.draw(data, options);
        }

        function drawUsedIdpsTable() {
            var data = new google.visualization.DataTable();

            data.addColumn(
                'string',
                '<?php echo $this->t('{proxystatistics:Proxystatistics:templates/tables_service_provider}'); ?>'
            );
            data.addColumn(
                'number',
                '<?php echo $this->t('{proxystatistics:Proxystatistics:templates/count}'); ?>'
            );
            data.addRows(
                [<?php DatabaseCommand::getAccessCountForServicePerIdentityProviders($lastDays, $spIdentifier)?>]
            );

            var table = new google.visualization.Table(document.getElementById('usedIdPsTable'));

            var options = {
                allowHtml: true
            };

            table.draw(data, options);
        }
    </script>
    </head>
    <body>
    <div class="go-to-stats-btn">
        <a href="./" class="btn btn-md btn-default"><span class="glyphicon glyphicon-home"></span>
            <?php echo $this->t('{proxystatistics:Proxystatistics:btn_label_back_to_stats}'); ?>
        </a>
    </div>

    <div class="timeRange">
        <h4><?php echo $this->t('{proxystatistics:Proxystatistics:templates_time_range}'); ?></h4>
        <form id="dateSelector" method="post">
            <label>
                <input id="1" type="radio" name="lastDays" value=0
                       onclick="this.form.submit()" <?php echo ($lastDays == 0) ? "checked=true" : "" ?>>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_all}'); ?>
            </label>
            <label>
                <input id="2" type="radio" name="lastDays" value=7
                       onclick="this.form.submit()" <?php echo ($lastDays == 7) ? "checked=true" : "" ?>>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_week}'); ?>
            </label>
            <label>
                <input id="3" type="radio" name="lastDays" value=30
                       onclick="this.form.submit()" <?php echo ($lastDays == 30) ? "checked=true" : "" ?>>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_month}'); ?>
            </label>
            <label>
                <input id="4" type="radio" name="lastDays" value=365
                       onclick="this.form.submit()" <?php echo ($lastDays == 365) ? "checked=true" : "" ?>>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_year}'); ?>
            </label>
        </form>
    </div>

    <h3><?php echo $this->t('{proxystatistics:Proxystatistics:templates/spDetail_dashboard_header}'); ?></h3>

    <div class="legend">
        <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/spDetail_dashboard_legend}'); ?></div>
    </div>

    <div id="loginsDashboard">
        <div id="line_div"></div>
        <div id="control_div"></div>
    </div>

    <h3><?php echo $this->t('{proxystatistics:Proxystatistics:templates/spDetail_graph_header}'); ?></h3>
    <div class="legend">
        <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/spDetail_graph_legend}'); ?></div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div id="usedIdPsChartDetail" class="pieChart"></div>
        </div>
        <div class="col-md-4">
            <div id="usedIdPsTable" class="table"></div>
        </div>
    </div>
    </body>
<?php

$this->includeAtTemplateBase('includes/footer.php');
