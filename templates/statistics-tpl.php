<?php

use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Logger;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */

const CONFIG_FILE_NAME = 'config.php';
const INSTANCE_NAME = 'instance_name';

$config = Configuration::getConfig(CONFIG_FILE_NAME);
$instanceName = $config->getString(INSTANCE_NAME, null);
if (!is_null($instanceName)) {
    $this->data['header'] = $instanceName . ' ' .
        $this->t('{proxystatistics:Proxystatistics:templates/statistics_header}');
} else {
    $this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/statistics_header}');
    Logger::warning('Missing configuration: config.php - instance_name is not set.');
}

$this->data['jquery'] = ['core' => true, 'ui' => true, 'css' => true];
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' .
    Module::getModuleUrl('proxystatistics/statisticsproxy.css') . '" />';
$this->data['head'] .= '';
$this->data['head'] .= '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
$this->data['head'] .= '<script type="text/javascript">
$(document).ready(function() {
	$("#tabdiv").tabs();
});
</script>';

$this->includeAtTemplateBase('includes/header.php');

if (!isset($_POST['lastDays'])) {
    $_POST['lastDays'] = 0;
}

if (!isset($_POST['tab'])) {
    $_POST['tab'] = 1;
}

?>

<div id="tabdiv">
    <ul class="tabset_tabs" width="100px">
        <li><a id="tab-1"
               href='<?php echo "summary.php?lastDays=" . $_POST['lastDays']; ?>'>
                <?php echo $this->t('{proxystatistics:Proxystatistics:summary}'); ?></a>
        </li>
        <li><a id="tab-2"
               href='<?php echo "identityProviders.php?lastDays=" . $_POST['lastDays']; ?>'>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_idpsDetail}'); ?></a>
        </li>
        <li><a id="tab-3"
               href='<?php echo "serviceProviders.php?lastDays=" . $_POST['lastDays']; ?>'>
                <?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_spsDetail}'); ?></a>
        </li>
    </ul>
</div>

<script>
    window.onload = function () {
        <?php echo "$('#tab-" . $_POST['tab'] . "').click();"; ?>
    }
</script>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>
