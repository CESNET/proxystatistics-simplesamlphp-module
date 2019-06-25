<?php

use SimpleSAML\Configuration;
use SimpleSAML\Module\proxystatistics\Auth\Process\DatabaseCommand;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */

?>

<?php
require 'timeRange.include.php';
?>

<h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_service_providers}'); ?></h2>
<div class="legend">
    <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/serviceProviders_legend}'); ?></div>
</div>
<div class="row">
    <div class="col-md-8">
        <div id="spsChartDetail" class="pieChart chart-spsChart"></div>
    </div>
    <div class="col-md-4">
        <div id="spsTable" class="table"></div>
    </div>
</div>
