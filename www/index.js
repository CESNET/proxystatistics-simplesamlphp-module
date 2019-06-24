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

  var view = null;
  if (viewCols) {
    view = new google.visualization.DataView(data);
    view.setColumns(viewCols);
  }

  var options = {
    pieSliceText: 'value',
    chartArea: {
      left: 20, top: 0, width: '100%', height: '100%'
    }
  };

  var chart = new google.visualization.PieChart(el);

  chart.draw(view || data, options);

  if (url) {
    var sh = selectHandler.bind(null, chart, data, url);
    google.visualization.events.addListener(chart, 'select', sh);
  }
}

var drawIdpsChart = drawPieChart.bind(null, ['sourceIdpName', 'sourceIdPEntityId', 'Count'], 'loginCountPerIdp', 2, [0, 2], 'idpDetail.php?entityId=');

var drawSpsChart = drawPieChart.bind(null, ['service', 'serviceIdentifier', 'Count'], 'accessCountPerService', 2, [0, 2], 'spDetail.php?identifier=');

function drawTable(cols, dataName, sortCol, viewCols, allowHTML, dateCol, url, getEl) {
  var el = getEl();
  if (!el) return;

  var data = new google.visualization.DataTable();

  var col = Object.keys(cols);
  for (var i = 0; i < col.length; i++) {
    data.addColumn(
      cols[col[i]], getTranslation(col[i])
    );
  }

  data.addRows(getStatisticsData(dataName));

  if (sortCol) {
    data.sort([{ column: sortCol, desc: true }]);
  }

  var view = null;
  if (viewCols) {
    view = new google.visualization.DataView(data);
    view.setColumns(viewCols);
  }

  var table = new google.visualization.Table(el);

  if (dateCol) {
    var formatter = new google.visualization.DateFormat({ pattern: 'MMMM  yyyy' });
    formatter.format(data, dateCol);
  }

  table.draw(view || data, allowHTML ? { allowHtml: true } : {});

  if (url) {
    var sh = selectHandler.bind(null, table, data, 'idpDetail.php?entityId=');
    google.visualization.events.addListener(table, 'select', sh);
  }
}

var drawIdpsTable = drawTable.bind(null, { tables_identity_provider: 'string', tables_identity_provider2: 'string', count: 'number' }, 'loginCountPerIdp', 2, [0, 2], false, null, 'idpDetail.php?entityId=');

var drawAccessedSpsChart = drawPieChart.bind(null, ['service', 'Count'], 'accessCountForIdentityProviderPerServiceProviders', 1, null, null);

var drawAccessedSpsTable = drawTable.bind(null, { tables_service_provider: 'string', count: 'number' }, 'accessCountForIdentityProviderPerServiceProviders', null, null, true, null, null);

var drawSpsTable = drawTable.bind(null, { tables_service_provider: 'string', count2: 'string', count: 'number' }, 'accessCountPerService', null, [0, 2], true, 0, 'spDetail.php?identifier=');

var drawUsedIdpsChart = drawPieChart.bind(null, ['service', 'Count'], 'accessCountForServicePerIdentityProviders', 1, null, null);

var drawUsedIdpsTable = drawTable.bind(null, { tables_service_provider: 'string', count: 'number' }, 'accessCountForServicePerIdentityProviders', null, null, true, null, 'spDetail.php?identifier=');

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
