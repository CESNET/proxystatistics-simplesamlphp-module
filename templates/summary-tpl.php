<?php
include dirname(__DIR__)."/lib/Auth/Process/DatabaseCommand.php";
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
?>
<link rel="stylesheet"  media="screen" type="text/css" href="<?php SimpleSAML\Module::getModuleUrl('proxystatistics/statisticsproxy.css')?>" />

<h2><?php echo $this->t('{proxystatistics:Proxystatistics:summary}'); ?></h2>
<div id="summary" >
    <script type="text/javascript">
        google.charts.load('current', {'packages':['table']});
        google.charts.setOnLoadCallback(drawTable);

        function drawTable() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', '<?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_name}'); ?>');
            data.addColumn('number', '<?php echo $this->t('{proxystatistics:Proxystatistics:templates/count}'); ?>');
            data.addRows([
                ['<?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_overall_logins}'); ?>', <?php DatabaseCommand::getCountOfAllLogins()?> ],
                ['<?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_accessed_providers}'); ?>', <?php DatabaseCommand::getCountOfAccesedServices()?>],
                ['<?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_used_identity_providers}'); ?>', <?php DatabaseCommand::getCountOfUsedIdp()?>],
                ['<?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_logins_today}'); ?>', <?php DatabaseCommand::getCountOfAllLoginsForToday()?>],
                ['<?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_average_logins}'); ?>', <?php DatabaseCommand::getAverageLoginCountPerDay()?>],
                ['<?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_max_logins}'); ?>', <?php DatabaseCommand::getMaxLoginCountPerDay()?>]
            ]);

            var table = new google.visualization.Table(document.getElementById('summaryTable'));

            table.draw(data, {showRowNumber: false, width: '80%', height: '300px'});
        }
    </script>

    <div id="summaryTable" ></div>
</div>
