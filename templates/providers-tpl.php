<?php declare(strict_types=1);

use SimpleSAML\Module\proxystatistics\Templates;

?>

<?php Templates::timeRange([
    'tab' => $this->data['tab'],
]); ?>
<h2>
    <?php
    echo $this->t('{proxystatistics:stats:side_' . $this->data['side'] . 's}');
    ?>
</h2>
<div class="legend">
    <div>
        <?php Templates::showLegend($this, $this->data['side']); ?>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <?php
        Templates::pieChart($this->data['side'] . 'Chart');
        ?>
    </div>
    <div class="col-md-4">
        <div id="<?php echo $this->data['side']; ?>Table" class="table-container"></div>
    </div>
</div>
