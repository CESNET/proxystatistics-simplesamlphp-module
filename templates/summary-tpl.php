<?php

use SimpleSAML\Module\proxystatistics\Auth\Process\DatabaseCommand;
use SimpleSAML\Module;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */

$lastDays = $this->data['lastDays'];

?>
<div class="timeRange">
    <h4><?php echo $this->t('{proxystatistics:Proxystatistics:templates_time_range}'); ?></h4>
    <form id="dateSelector" method="post">
        <input name="tab" value="0" type="hidden">
        <label>
            <input id="1" type="radio" name="lastDays" value=0
                    <?php echo ($lastDays == 0) ? "checked=true" : "" ?>>
            <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_all}'); ?>
        </label>
        <label>
            <input id="2" type="radio" name="lastDays" value=7
                    <?php echo ($lastDays == 7) ? "checked=true" : "" ?>>
            <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_week}'); ?>
        </label>
        <label>
            <input id="3" type="radio" name="lastDays" value=30
                    <?php echo ($lastDays == 30) ? "checked=true" : "" ?>>
            <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_month}'); ?>
        </label>
        <label>
            <input id="4" type="radio" name="lastDays" value=365
                    <?php echo ($lastDays == 365) ? "checked=true" : "" ?>>
            <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_year}'); ?>
        </label>
    </form>
</div>

<h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_logins}'); ?></h2>
<div class="legend-logins">
    <div>
        <?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_logins_info}'); ?>
    </div>
</div>
<div id="loginsDashboard">
    <div id="line_div"></div>
    <div id="control_div"></div>
</div>

<div class="row tableMaxHeight">
    <div class="col-md-6 graph">
        <h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_id_providers}'); ?></h2>
        <div class="legend">
            <div id="summaryIdp">
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_idps_info}'); ?>
            </div>
        </div>
        <div id="idpsChart" class="pieChart chart-idpsChart"></div>
    </div>
    <div class="col-md-6">
        <h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_service_providers}'); ?></h2>
        <div class="legend">
            <div>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_sps_info}'); ?>
            </div>
        </div>
        <div id="spsChart" class="pieChart chart-spsChart"></div>
    </div>
</div>
