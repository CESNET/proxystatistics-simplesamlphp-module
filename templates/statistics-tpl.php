<?php
include dirname(__DIR__)."/lib/Auth/Process/DatabaseCommand.php";
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */
$this->data['header'] = $this->t('{proxystatistics:Proxystatistics:templates/statistics_header}');

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
        <li><a href='summary.php'><?php echo $this->t('{proxystatistics:Proxystatistics:summary}'); ?></a></li>
        <li><a href='graphs.php'><?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_graphs}'); ?></a></li>
        <li><a href='tables.php'><?php echo $this->t('{proxystatistics:Proxystatistics:templates/statistics-tpl_tables}'); ?></a></li>
    </ul>

</div>

<?php
$this->includeAtTemplateBase('includes/footer.php');

?>
