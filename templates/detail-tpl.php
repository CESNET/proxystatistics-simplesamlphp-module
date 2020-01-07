<?php

/**
 * @author Pavel Břoušek <brousek@ics.muni.cz>
 */

use SimpleSAML\Module\proxystatistics\Templates;

$this->includeAtTemplateBase('includes/header.php');
?>
    </head>
    <body>
        <div class="go-to-stats-btn">
            <a href="./" class="btn btn-md btn-default">
                <span class="glyphicon glyphicon-home"></span>
                <?php echo $this->t('{proxystatistics:stats:back_to_stats}'); ?>
            </a>
        </div>
        <?php Templates::timeRange(['side' => $this->data['side'], 'id' => $this->data['id']]); ?>
        <h3><?php echo $this->t('{proxystatistics:stats:' . $this->data['side'] . 'Detail_dashboard_header}'); ?></h3>
        <div class="legend">
            <div>
                <?php echo $this->t('{proxystatistics:stats:' . $this->data['side'] . 'Detail_dashboard_legend}'); ?>
            </div>
        </div>
        <?php Templates::loginsDashboard(); ?>
        <div class="<?php echo $this->data['detailGraphClass'] ?>">
            <h3><?php echo $this->t('{proxystatistics:stats:' . $this->data['side'] . 'Detail_graph_header}'); ?></h3>
            <div class="legend">
                <div>
                    <?php echo $this->t('{proxystatistics:stats:' . $this->data['side'] . 'Detail_graph_legend}'); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <?php Templates::pieChart('detail' . $this->data['other_side'] . 'Chart'); ?>
                </div>
                <div class="col-md-4">
                    <div id="detail<?php echo $this->data['other_side']; ?>Table" class="table-container"></div>
                </div>
            </div>
        </div>
    </body>
<?php
$this->includeAtTemplateBase('includes/footer.php');
