<?php

/**
 * @author Pavel Břoušek <brousek@ics.muni.cz>
 */

?>
<div class="timeRange">
    <h4><?php echo $this->t('{proxystatistics:stats:select_time_range}'); ?></h4>
    <form id="dateSelector" method="GET">
        <?php
        foreach (['tab', 'side', 'id'] as $var) {
            if (isset($this->data[$var])) {
                ?>
                <input name="<?php echo $var; ?>" type="hidden"
                    value="<?php echo htmlspecialchars($this->data[$var]); ?>">
                <?php
            }
        }
        ?>
        <?php
        $values = [0 => 'all', 7 => 'week', 30 => 'month', 365 => 'year'];
        $i = 0;
        ?>
        <?php foreach ($values as $value => $str) : ?>
            <label>
                <input id="<?php echo $i; ?>" type="radio" name="lastDays" value="<?php echo $value; ?>"
                        <?php echo $this->data['lastDays'] === $value ? 'checked=true' : '' ?>>
                <?php echo $this->t('{proxystatistics:stats:time_range_' . $str . '}'); ?>
            </label>
            <?php $i++; ?>
        <?php endforeach; ?>
    </form>
</div>
