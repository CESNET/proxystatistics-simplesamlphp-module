<?php
include dirname(__DIR__)."/lib/Auth/Process/DatabaseCommand.php";
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
$this->data['header'] = 'SimpleSAMLphp Statistics';

$this->data['jquery'] = array('core' => TRUE, 'ui' => TRUE, 'css' => TRUE);
$this->data['head'] = '<link rel="stylesheet"  media="screen" type="text/css" href="' . SimpleSAML\Module::getModuleUrl('proxystatistics/statisticsproxy.css')  . '" />';
$this->data['head'] .='';
$this->data['head'] .= '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
$this->data['head'] .= '<script type="text/javascript">
$(document).ready(function() {
	$("#tabdiv").tabs();
});
</script>';
$this->includeAtTemplateBase('includes/header.php');
?>
<div id="tabdiv">
    <ul class="tabset_tabs" width="100px">
        <li><a href='summary.php'>Summary</a></li>
        <li><a href='graphs.php'>Graphs</a></li>
        <li><a href='tables.php'>Tables</a></li>
    </ul>

</div>

<?php
$this->includeAtTemplateBase('includes/footer.php');

?>
