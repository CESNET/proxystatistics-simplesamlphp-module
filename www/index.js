'use strict';

/* global google */

function getStatisticsData(name) {
  return $.parseJSON($('#' + name).attr('content'));
}

function getStatisticsDataYMDC(name) {
  return getStatisticsData(name).map(function mapItemToDate(item) {
    return [new Date(item.year, item.month - 1, item.day), { v: item.count }];
  });
}

function getTranslation(str) {
  return $.parseJSON($('#translations').attr('content'))[str];
}

function selectHandler(obj, data, url) {
  var selection = obj.getSelection();
  if (selection.length) {
    var id = data.getValue(selection[0].row, 1);
    window.location.href = url + id;
  }
}

function drawLoginsChart(getEl) {
  var el = getEl();
  if (!el) return;

  var data = google.visualization.arrayToDataTable([['Date', 'Count']].concat(getStatisticsDataYMDC('loginCountPerDay')));

  var dashboard = new google.visualization.Dashboard(el);

  var chartRangeFilter = new google.visualization.ControlWrapper({
    controlType: 'ChartRangeFilter',
    containerId: 'control_div',
    options: {
      filterColumnLabel: 'Date'
    }
  });
  var chart = new google.visualization.ChartWrapper({
    chartType: 'LineChart',
    containerId: 'line_div',
    options: {
      legend: 'none'
    }
  });
  dashboard.bind(chartRangeFilter, chart);
  dashboard.draw(data);
}

function drawPieChart(colNames, dataName, sortCol, viewCols, url, getEl) {
  var el = getEl();
  if (!el) return;

  var data = google.visualization.arrayToDataTable([colNames].concat(getStatisticsData(dataName)));
  data.sort([{ column: sortCol, desc: true }]);

  if (viewCols) {
    var view = new google.visualization.DataView(data);
    view.setColumns(viewCols);
  }

  var options = {
    pieSliceText: 'value',
    chartArea: {
      left: 20, top: 0, width: '100%', height: '100%'
    }
  };

  var chart = new google.visualization.PieChart(el);

  chart.draw(view ? view : data, options);

  if (url) {
    var sh = selectHandler.bind(null, chart, data, url);
    google.visualization.events.addListener(chart, 'select', sh);
  }
}

drawIdpsChart = drawPieChart.bind(null, ['sourceIdpName', 'sourceIdPEntityId', 'Count'], 'loginCountPerIdp', 2, [0, 2], 'idpDetail.php?entityId=');

drawSpsChart = drawPieChart.bind(null, ['service', 'serviceIdentifier', 'Count'], 'accessCountPerService', 2, [0, 2], 'spDetail.php?identifier=');

function drawIdpsTable(getEl) {
  var el = getEl();
  if (!el) return;

  var data = new google.visualization.DataTable();

  data.addColumn(
    'string', getTranslation('tables_identity_provider')
  );
  data.addColumn(
    'string', getTranslation('tables_identity_provider')
  );
  data.addColumn(
    'number', getTranslation('count')
  );
  data.addRows(getStatisticsData('loginCountPerIdp'));

  data.sort([{ column: 2, desc: true }]);

  var view = new google.visualization.DataView(data);

  view.setColumns([0, 2]);

  var table = new google.visualization.Table(el);

  table.draw(view);

  var sh = selectHandler.bind(null, table, data, 'idpDetail.php?entityId=');
  google.visualization.events.addListener(table, 'select', sh);
}

drawAccessedSpsChart = drawPieChart.bind(null, ['service', 'Count'], 'accessCountForIdentityProviderPerServiceProviders', 1, null, null);

function drawAccessedSpsTable(getEl) {
  var el = getEl();
  if (!el) return;

  var data = new google.visualization.DataTable();

  data.addColumn(
    'string',
    getTranslation('tables_service_provider')
  );
  data.addColumn(
    'number',
    getTranslation('count')
  );
  data.addRows(
    getStatisticsData('accessCountForIdentityProviderPerServiceProviders')
  );

  var table = new google.visualization.Table(el);

  var options = {
    allowHtml: true
  };

  table.draw(data, options);
}

function drawSpsTable(getEl) {
  var el = getEl();
  if (!el) return;

  var data = new google.visualization.DataTable();

  data.addColumn(
    'string',
    getTranslation('tables_service_provider')
  );
  data.addColumn(
    'string',
    getTranslation('count')
  );
  data.addColumn(
    'number',
    getTranslation('count')
  );
  data.addRows(
    getStatisticsData('accessCountPerService')
  );

  var view = new google.visualization.DataView(data);

  view.setColumns([0, 2]);

  var table = new google.visualization.Table(el);

  var formatter = new google.visualization.DateFormat({ pattern: 'MMMM  yyyy' });
  formatter.format(data, 0); // Apply formatter to second column
  var options = {
    allowHtml: true
  };

  table.draw(view, options);

  var sh = selectHandler.bind(null, table, data, 'spDetail.php?identifier=');
  google.visualization.events.addListener(table, 'select', sh);
}

drawUsedIdpsChart = drawPieChart.bind(null, ['service', 'Count'], 'accessCountForServicePerIdentityProviders', 1, null, null);

function drawUsedIdpsTable(getEl) {
  var el = getEl();
  if (!el) return;

  var data = new google.visualization.DataTable();

  data.addColumn(
    'string',
    getTranslation('tables_service_provider')
  );
  data.addColumn(
    'number',
    getTranslation('count')
  );
  data.addRows(
    getStatisticsData('accessCountForServicePerIdentityProviders')
  );

  var table = new google.visualization.Table(el);

  var options = {
    allowHtml: true
  };

  table.draw(data, options);
}

function getterLoadCallback(getEl, callback) {
  google.charts.setOnLoadCallback(callback.bind(null, getEl));
}

function classLoadCallback(className, callback) {
  getterLoadCallback(function () { return $('.' + className + ':visible')[0]; }, callback); // eslint-disable-line func-names
}

function idLoadCallback(id, callback) {
  getterLoadCallback(document.getElementById.bind(document, id), callback);
}

function chartInit() {
  idLoadCallback('loginsDashboard', drawLoginsChart);
  classLoadCallback('chart-idpsChart', drawIdpsChart);
  classLoadCallback('chart-spsChart', drawSpsChart);
  idLoadCallback('idpsTable', drawIdpsTable);
  idLoadCallback('accessedSpsChartDetail', drawAccessedSpsChart);
  idLoadCallback('accessedSpsTable', drawAccessedSpsTable);
  idLoadCallback('spsTable', drawSpsTable);
  idLoadCallback('usedIdPsChartDetail', drawUsedIdpsChart);
  idLoadCallback('usedIdPsTable', drawUsedIdpsTable);
  $('#dateSelector input[name=lastDays]').on('click', function submitForm() {
    this.form.submit();
  });
}

$(document).ready(function docReady() {
  google.charts.load('current', { packages: ['corechart', 'controls', 'table'] });

  $('#tabdiv').tabs({
    selected: $('#tabdiv').data('activetab'),
    load: chartInit
  });
  chartInit();
});
