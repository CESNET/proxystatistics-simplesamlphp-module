<?php
include dirname(__DIR__) . "/lib/Auth/Process/DatabaseCommand.php";
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
$lastDays = $this->data['lastDays'];

?>
<link rel="stylesheet" media="screen" type="text/css"
      href="<?php SimpleSAML_Module::getModuleUrl('proxystatistics/statisticsproxy.css') ?>"/>
<script type="text/javascript">
    google.charts.load('current', {'packages': ['corechart', 'table']});
    google.charts.setOnLoadCallback(drawIdpsChart);
    google.charts.setOnLoadCallback(drawIdpsTable);

    function drawIdpsChart() {
        var data = google.visualization.arrayToDataTable([
            ['sourceIdp', 'sourceIdPEntityId', 'Count'],
            <?php DatabaseCommand::getLoginCountPerIdp($lastDays)?>
        ]);

        data.sort([{column: 2, desc: true}]);

        var view = new google.visualization.DataView(data);

        view.setColumns([0, 2]);

        var options = {
            pieSliceText: 'value',
            chartArea: {left: 20, top: 0, width: '100%', height: '100%'},
        };

        var chart = new google.visualization.PieChart(document.getElementById('idpsChartDetail'));
        chart.draw(view, options);

        google.visualization.events.addListener(chart, 'select', selectHandler);

        function selectHandler() {
            var selection = chart.getSelection();
            if (selection.length) {
                var entityId = data.getValue(selection[0].row, 1);
                window.location.href = 'idpDetail.php?entityId=' + entityId;
            }
        }
    }

    function drawIdpsTable() {
        var data = new google.visualization.DataTable();

        data.addColumn(
            'string', '<?php echo $this->t('{proxystatistics:Proxystatistics:templates/tables_identity_provider}'); ?>'
        );
        data.addColumn(
            'string', '<?php echo $this->t('{proxystatistics:Proxystatistics:templates/tables_identity_provider}'); ?>'
        );
        data.addColumn(
            'number', '<?php echo $this->t('{proxystatistics:Proxystatistics:templates/count}'); ?>'
        );
        data.addRows([<?php DatabaseCommand::getLoginCountPerIdp($lastDays)?>]);

        data.sort([{column: 2, desc: true}]);

        var view = new google.visualization.DataView(data);

        view.setColumns([0, 2]);

        var table = new google.visualization.Table(document.getElementById('idpsTable'));

        table.draw(view);

        google.visualization.events.addListener(table, 'select', selectHandler);

        function selectHandler() {
            var selection = table.getSelection();
            if (selection.length) {
                var entityId = data.getValue(selection[0].row, 1);
                window.location.href = 'idpDetail.php?entityId=' + entityId;
            }
        }
    }

</script>
</head>

<body>
<div class="timeRange">
    <h4><?php echo $this->t('{proxystatistics:Proxystatistics:templates_time_range}'); ?></h4>
    <form id="dateSelector" method="post">
        <input name="tab" value="2" hidden>
        <label>
            <input id="1" type="radio" name="lastDays" value=0
                   onclick="this.form.submit()"<?php echo ($lastDays == 0) ? "checked=true" : "" ?>>
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

<h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_id_providers}'); ?></h2>
<div class="legend">
    <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/identityProviders_legend}'); ?></div>
</div>
<div class="row">
    <div class="col-md-8">
        <div id="idpsChartDetail" class="pieChart"></div>
    </div>
    <div class="col-md-4">
        <div id="idpsTable" class="table"></div>
    </div>
</div>
</body>
