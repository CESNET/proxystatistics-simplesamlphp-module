<?php declare(strict_types=1);

use SimpleSAML\Module\proxystatistics\Config;
use SimpleSAML\Module\proxystatistics\Templates;

?>

<?php Templates::timeRange([
    'tab' => $this->data['tab'],
]); ?>

<h2><?php echo $this->t('{proxystatistics:stats:graphs_logins}'); ?></h2>
<div class="legend-logins">
    <div>
        <?php echo $this->t('{proxystatistics:stats:summary_logins_info}'); ?>
    </div>
    <?php Templates::loginsDashboard(); ?>
</div>

<div class="row tableMaxHeight">
    <?php foreach (Config::SIDES as $side) : ?>
        <div class="<?php echo $this->data['summaryGraphs'][$side]['Providers'] ?>">
            <h2>
                <?php echo $this->t('{proxystatistics:stats:side_' . $side . 's}'); ?>
            </h2>
            <div class="row">
                <div class="<?php echo $this->data['summaryGraphs'][$side]['ProvidersLegend'] ?>">
                    <div class="legend">
                        <div id="summary<?php echo $side; ?>">
                            <?php Templates::showLegend($this, $side); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="<?php echo $this->data['summaryGraphs'][$side]['ProvidersGraph'] ?>">
                    <?php Templates::pieChart($side . 'Chart'); ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
