'use strict';

/* global Chart moment */

function getStatisticsData(name) {
  return $.parseJSON($('#' + name).attr('content'));
}

function getStatisticsDataYMDC(name) {
  return getStatisticsData(name).map(function mapItemToDate(item) {
    return {
      t: new Date(item.year, item.month - 1, item.day),
      y: item.count
    };
  });
}

function getTranslation(str) {
  return $.parseJSON($('#translations').attr('content'))[str];
}

function drawLoginsChart(getEl) {
  var el = getEl();
  if (!el) return;

  var ctx = el.getContext('2d');

  var data = getStatisticsDataYMDC('loginCountPerDay');

  var minX = data[0].t;
  var maxX = data[data.length - 1].t;

  var i = 0;
  var extendedData = [];
  for (var d = new Date(minX); d <= maxX; d.setDate(d.getDate() + 1)) {
    if (data[i].t.getTime() === d.getTime()) {
      extendedData.push(data[i]);
      i += 1;
    } else if (data[i].t.getTime() > d.getTime()) {
      extendedData.push({ t: new Date(d), y: 0 });
    } else {
      throw new Error("Data is not sorted");
    }
  }
  data = extendedData;

  new Chart(ctx, { // eslint-disable-line no-new
    type: 'bar',
    options: {
      responsive: true,
      maintainAspectRatio: false,
      ticks: {
        beginAtZero: true
      },
      scales: {
        xAxes: [{
          type: 'time',
          time: { // do not set round: 'day', because it breaks zooming out from 7 or fewer days
            isoWeekday: true,
            minUnit: 'day',
            tooltipFormat: 'l'
          }
        }],
        yAxes: [{
          scaleLabel: {
            display: false
          }
        }]
      },
      legend: {
        display: false
      },
      tooltips: {
        intersect: false,
        mode: 'index',
        callbacks: {
          label: function showLabel(tooltipItem, myData) {
            var label = myData.datasets[tooltipItem.datasetIndex].label || '';
            if (label) {
              label += ': ';
            }
            label += parseInt(tooltipItem.value, 10);
            return label;
          }
        }
      },
      pan: {
        enabled: true,
        mode: 'x',
        rangeMin: {
          x: minX
        },
        rangeMax: {
          x: maxX
        }
      },
      zoom: {
        enabled: true,
        drag: false,
        mode: 'x',
        rangeMin: {
          x: minX
        },
        rangeMax: {
          x: maxX
        }
      }
    },
    "data": {
        "datasets": [
            {
                "label": "",
                "data": data,
                type: 'line',
                pointRadius: 0,
                fill: false,
                lineTension: 0,
                borderWidth: 2,
                backgroundColor: '#00F',
                borderColor: '#00F'
            }
        ]
    }
  });
}

var pieColors = [
  '#3366CC', '#DC3912', '#FF9900', '#109618', '#990099', '#3B3EAC', '#0099C6', '#DD4477', '#66AA00', '#B82E2E',
  '#316395', '#994499', '#22AA99', '#AAAA11', '#6633CC', '#E67300', '#8B0707', '#329262', '#5574A6', '#3B3EAC',
  '#3366CC', '#DC3912', '#FF9900', '#109618', '#990099', '#3B3EAC', '#0099C6', '#DD4477', '#66AA00', '#B82E2E',
  '#316395', '#994499', '#22AA99', '#AAAA11', '#6633CC', '#E67300', '#8B0707', '#329262', '#5574A6', '#3B3EAC'
];

var minPieFraction = 0.005;
var minPieOtherFraction = 0.01;
var maxPieOtherFraction = 0.20;
var pieOtherOnlyIfNeeded = false;

function processDataForPieChart(data, viewCols) {
  if (pieOtherOnlyIfNeeded && data.length <= pieColors.length) {
    return data;
  }
  var col = viewCols || [0, 1];
  var total = data.reduce(function getSum(accumulator, currentValue) {
    return accumulator + currentValue[col[1]];
  }, 0);
  var i = data.length;
  var othersFraction = 0;
  while (i > 1
    && (i > pieColors.length
    || (data[i - 1][col[1]] / total < minPieFraction
      && data[i - 1][col[1]] / total + othersFraction < maxPieOtherFraction))) {
    i -= 1;
    othersFraction += data[i][col[1]] / total;
  }
  if (othersFraction < minPieOtherFraction) {
    i = Math.min(data.length, pieColors.length);
    othersFraction = 0;
  }
  var processedData = data.slice(0, i);
  if (i < data.length && othersFraction > 0) {
    var theOthers = [null, null, null];
    theOthers[col[1]] = Math.round(othersFraction * total);
    theOthers[col[0]] = getTranslation('other');
    processedData.push(theOthers);
  }
  return { data: processedData, other: othersFraction > 0, total: total };
}

function drawPieChart(colNames, dataName, viewCols, url, getEl) {
  var el = getEl();
  if (!el) return;

  var ctx = el.getContext('2d');
  var processedData = processDataForPieChart(getStatisticsData(dataName), viewCols);
  var data = processedData.data;
  var other = processedData.other;
  var total = processedData.total;
  var col = viewCols || [0, 1];
  var colors = pieColors.slice();
  if (other) {
    colors[data.length - 1] = '#DDDDDD';
  }
  var chart = new Chart(ctx, {
      type: 'pie',
      data: {
          labels: data.map(function getFirst(row) { return row[col[0]]; }),
          datasets: [{
              data: data.map(function getSecond(row) { return row[col[1]]; }),
              backgroundColor: colors,
              borderWidth: 1
          }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
          display: false
        },
        tooltips: {
          callbacks: {
            label: function generateLabel(tooltipItem, myData) {
              var label = myData.datasets[tooltipItem.datasetIndex].label || '';

              if (label) {
                  label += ': ';
              }
              var value = myData.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
              label += value + ' (';
              label += Math.round((value / total) * 1000) / 10;
              label += ' %)';
              return label;
            }
          }
        },
        legendCallback: function legendCallback(c) {
          var text = [];
          text.push('<ul class="chart-legend">');

          var datasets = c.data.datasets;
          var labels = c.data.labels;

          if (datasets.length) {
            for (var i = 0; i < datasets[0].data.length; ++i) {
              var liClasses = ['chart-legend-item'];
              if (other && i === datasets[0].data.length - 1) {
                liClasses.push('other');
              }
              text.push('<li class="' + liClasses.join(' ') + '">');
              if (labels[i]) {
                var label = labels[i];
                if (url && (!other || i < datasets[0].data.length - 1)) {
                  label = '<a href="' + url + encodeURIComponent(data[i][1]) + '" class="item">' + label + '</a>';
                } else {
                  label = '<span class="item">' + label + '</span>';
                }
                text.push(label);
              }
              text.push('</li>');
            }
          }

          text.push('</ul>');
          return text.join('');
        }
      }
  });
  if (url) {
    el.addEventListener('click', function pieClick(evt) {
      var activePoints = chart.getElementsAtEvent(evt);
      if (activePoints[0]
        && data[activePoints[0]._index][1] !== null) { // eslint-disable-line no-underscore-dangle
        window.location.href = url + encodeURIComponent(
          data[activePoints[0]._index][1] // eslint-disable-line no-underscore-dangle
        );
      }
    });
  }
  var legendContainer = el.parentNode.parentNode.querySelector('.legend-container');
  legendContainer.innerHTML = chart.generateLegend();
}

var drawIdpsChart = drawPieChart.bind(null, ['sourceIdpName', 'sourceIdPEntityId', 'Count'], 'loginCountPerIdp',
  [0, 2], getStatisticsData('module_url_base') + 'idpDetail.php?entityId=');

var drawSpsChart = drawPieChart.bind(null, ['service', 'serviceIdentifier', 'Count'], 'accessCountPerService',
  [0, 2], getStatisticsData('module_url_base') + 'spDetail.php?identifier=');

function drawCountTable(cols, dataCol, countCol, dataName, allowHTML, url, getEl) {
  var el = getEl();
  if (!el) return;

  var viewCols = [dataCol, countCol];

  var tableDiv = el.appendChild(document.createElement('div'));
  tableDiv.className = 'table-responsive';

  var table = tableDiv.appendChild(document.createElement('table'));
  table.className = 'table table-striped table-hover table-condensed';

  var data = getStatisticsData(dataName);

  var thead = table.appendChild(document.createElement('thead'));
  var tr = thead.appendChild(document.createElement('tr'));
  var th;
  var i;
  for (i = 0; i < viewCols.length; i++) {
    th = tr.appendChild(document.createElement('th'));
    th.innerText = getTranslation(cols[i]);
    if (viewCols[i] === countCol) {
      th.className = 'text-right';
    }
  }

  var tbody = table.appendChild(document.createElement('tbody'));
  var td;
  var a;
  for (var j = 0; j < data.length; j++) {
    tr = tbody.appendChild(document.createElement('tr'));
    for (i = 0; i < viewCols.length; i++) {
      td = tr.appendChild(document.createElement('td'));
      if (viewCols[i] === countCol) {
        td.className = 'text-right';
      }
      if (url && viewCols[i] === dataCol) {
        a = document.createElement('a');
        a[allowHTML ? 'innerHTML' : 'innerText'] = data[j][viewCols[i]];
        a.href = url + encodeURIComponent(data[j][1]);
        td.appendChild(a);
      } else {
        td[allowHTML ? 'innerHTML' : 'innerText'] = data[j][viewCols[i]];
      }
    }
  }

  el.addEventListener('scroll', function floatingTableHead() {
    var scrolling = el.scrollTop > 0;
    el.classList.toggle('scrolling', scrolling);
    el.querySelectorAll('th').forEach(function floatTh(the) {
      the.style.transform = scrolling ? ('translateY(' + el.scrollTop + 'px)') : ''; // eslint-disable-line no-param-reassign
    });
  });
}

var drawIdpsTable = drawCountTable.bind(null, ['tables_identity_provider', 'count'], 0, 2,
  'loginCountPerIdp', false, getStatisticsData('module_url_base') + 'idpDetail.php?entityId=');

var drawAccessedSpsChart = drawPieChart.bind(null, ['service', 'Count'],
  'accessCountForIdentityProviderPerServiceProviders', null, null);

var drawAccessedSpsTable = drawCountTable.bind(null, ['tables_service_provider', 'count'], 0, 1,
  'accessCountForIdentityProviderPerServiceProviders', true, null);

var drawSpsTable = drawCountTable.bind(null, ['tables_service_provider', 'count'], 0, 2,
  'accessCountPerService', true, getStatisticsData('module_url_base') + 'spDetail.php?identifier=');

var drawUsedIdpsChart = drawPieChart.bind(null, ['service', 'Count'],
  'accessCountForServicePerIdentityProviders', null, null);

var drawUsedIdpsTable = drawCountTable.bind(null, ['tables_identity_provider', 'count'], 0, 1,
  'accessCountForServicePerIdentityProviders', true, null);

function getterLoadCallback(getEl, callback) {
  callback(getEl);
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
  Chart.platform.disableCSSInjection = true;
  var loginsDashboard = document.getElementById('loginsDashboard');
  if (loginsDashboard !== null && loginsDashboard.dataset.locale === 'cs') {
    moment.locale(loginsDashboard.dataset.locale);
  }

  $('#tabdiv').tabs({
    selected: $('#tabdiv').data('activetab'),
    load: chartInit
  });
  chartInit();
});
