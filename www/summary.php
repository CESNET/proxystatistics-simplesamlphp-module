<?php
include dirname(__DIR__)."/lib/Auth/Process/DatabaseCommand.php";
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
?>
<link rel="stylesheet"  media="screen" type="text/css" href="<?php SimpleSAML\Module::getModuleUrl('proxystatistics/statisticsproxy.css')?>" />

<h2>Summary</h2>
<div id="summary" >
    <script type="text/javascript">
        google.charts.load('current', {'packages':['table']});
        google.charts.setOnLoadCallback(drawTable);

        function drawTable() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Name');
            data.addColumn('number', 'Count');
            data.addRows([
                ['Overall number of logins', <?php DatabaseCommand::getCountOfAllLogins()?> ],
                ['Total number of accessed service providers', <?php DatabaseCommand::getCountOfAccesedServices()?>],
                ['Total number of used identity providers', <?php DatabaseCommand::getCountOfUsedIdp()?>],
                ['Number of logins for today', <?php DatabaseCommand::getCountOfAllLoginsForToday()?>],
                ['Average number of logins per day', <?php DatabaseCommand::getAverageLoginCountPerDay()?>],
                ['Maximal number of logins per day', <?php DatabaseCommand::getMaxLoginCountPerDay()?>]
                ]);

            var table = new google.visualization.Table(document.getElementById('summaryTable'));

            table.draw(data, {showRowNumber: false, width: '80%', height: '300px'});
        }
    </script>

    <div id="summaryTable" ></div>
</div>
