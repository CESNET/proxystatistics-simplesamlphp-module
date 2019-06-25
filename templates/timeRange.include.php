<div class="timeRange">
    <h4><?php echo $this->t('{proxystatistics:Proxystatistics:templates_time_range}'); ?></h4>
    <form id="dateSelector" method="post">
        <?php if (isset($this->data['tab'])) : ?>
            <input name="tab" value="<?php echo $this->data['tab'];?>" type="hidden">
        <?php endif; ?>
        <?php
        $values = [0=>'all', 7=>'week', 30=>'month', 365=>'year'];
        $i = 0;
        ?>
        <?php foreach ($values as $value => $str) : ?>
            <label>
                <input id="<?php echo $i;?>" type="radio" name="lastDays" value="<?php echo $value;?>"
                        <?php echo ($this->data['lastDays'] == $value) ? "checked=true" : "" ?>>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_'.$str.'}'); ?>
            </label>
            <?php $i++; ?>
        <?php endforeach; ?>
    </form>
</div>
