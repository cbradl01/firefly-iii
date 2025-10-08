/*
 * charts.js
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
/** global: Chart, defaultChartOptions, accounting, defaultPieOptions, noDataForChart, todayText */
var allCharts = {};


/*
 Make some colours:
 */
var colourSet = [
    [53, 124, 165],
    [0, 141, 76], // green
    [219, 139, 11],
    [202, 25, 90], // paars rood-ish #CA195A
    [85, 82, 153],
    [66, 133, 244],
    [219, 68, 55], // red #DB4437
    [244, 180, 0],
    [15, 157, 88],
    [171, 71, 188],
    [0, 172, 193],
    [255, 112, 67],
    [158, 157, 36],
    [92, 107, 192],
    [240, 98, 146],
    [0, 121, 107],
    [194, 24, 91]
];

var fillColors = [];

for (var i = 0; i < colourSet.length; i++) {
    fillColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.5)");
}

Chart.defaults.plugins.legend.display = false;
Chart.defaults.animation.duration = 0;
Chart.defaults.responsive = true;
Chart.defaults.maintainAspectRatio = false;

/**
 *
 * @param data
 * @returns {{}}
 */
function colorizeData(data) {
    var newData = {};
    newData.datasets = [];

    for (var loop = 0; loop < data.count; loop++) {
        newData.labels = data.labels;
        var dataset = data.datasets[loop];
        dataset.fill = false;
        dataset.backgroundColor = dataset.borderColor = fillColors[loop];
        newData.datasets.push(dataset);
    }
    return newData;
}

/**
 * Function to draw a line chart:
 * @param URL
 * @param container
 */
function lineChart(URL, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    var chartType = 'line';

    drawAChart(URL, container, chartType, options, colorData);
}

/**
 * Function to draw a line chart that doesn't start at ZERO.
 * @param URL
 * @param container
 */
function lineNoStartZeroChart(URL, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    var chartType = 'line';
    options.scales.y.ticks.beginAtZero = false;

    drawAChart(URL, container, chartType, options, colorData);
}

/**
 * Overrules the currency the line chart is drawn in.
 *
 * @param URL
 * @param container
 */
function otherCurrencyLineChart(URL, container, currencySymbol) {
    "use strict";

    var colorData = true;

    var newOpts = {
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    // break ticks when too long.
                    callback: function (value, index, values) {
                        return formatLabel(value, 20);
                    }
                }
            },
            y: {
                display: true,
                //hello: 'fresh',
                ticks: {
                    callback: function (tickValue) {
                        "use strict";
                        // use first symbol or null:
                        return accounting.formatMoney(tickValue);

                    },
                    beginAtZero: true
                }
            }
        },
    };

    //var options = $.extend(true, newOpts, defaultChartOptions);
    var options = $.extend(true, defaultChartOptions, newOpts);

    console.log(options);
    var chartType = 'line';

    drawAChart(URL, container, chartType, options, colorData);
}

/**
 * Function to draw a chart with double Y Axes and stacked columns.
 *
 * @param URL
 * @param container
 */
function doubleYChart(URL, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    options.scales.y = {
        display: true,
        ticks: {
            callback: function (tickValue) {
                "use strict";
                return accounting.formatMoney(tickValue);

            },
            beginAtZero: true
        },
        position: "left",
        "id": "y-axis-0"
    };
    options.scales.y1 = {
        display: true,
        ticks: {
            callback: function (tickValue) {
                "use strict";
                return accounting.formatMoney(tickValue);

            },
            beginAtZero: true
        },
        position: "right",
        "id": "y-axis-1"
    };
    options.stacked = true;
    options.scales.x.stacked = true;

    var chartType = 'bar';

    drawAChart(URL, container, chartType, options, colorData);
}

/**
 * Function to draw a chart with double Y Axes and non stacked columns.
 *
 * @param URL
 * @param container
 */
function doubleYNonStackedChart(URL, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    options.scales.y = {
        display: true,
        ticks: {
            callback: function (tickValue) {
                "use strict";
                return accounting.formatMoney(tickValue);

            },
            beginAtZero: true
        },
        position: "left",
        "id": "y-axis-0"
    };
    options.scales.y1 = {
        display: true,
        ticks: {
            callback: function (tickValue) {
                "use strict";
                return accounting.formatMoney(tickValue);

            },
            beginAtZero: true
        },
        position: "right",
        "id": "y-axis-1"
    };
    var chartType = 'bar';

    drawAChart(URL, container, chartType, options, colorData);
}


/**
 *
 * @param URL
 * @param container
 */
function columnChart(URL, container) {
    "use strict";
    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);
    var chartType = 'bar';

    drawAChart(URL, container, chartType, options, colorData);
}



/**
 *
 * @param URL
 * @param container
 */
function columnChartCustomColours(URL, container) {
    "use strict";
    var colorData = false;
    var options = $.extend(true, {}, defaultChartOptions);
    var chartType = 'bar';

    drawAChart(URL, container, chartType, options, colorData);

}

/**
 *
 * @param URL
 * @param container
 */
function stackedColumnChart(URL, container) {
    "use strict";

    var colorData = true;
    var options = $.extend(true, {}, defaultChartOptions);

    options.stacked = true;
    options.scales.x.stacked = true;
    options.scales.y.stacked = true;

    var chartType = 'bar';

    drawAChart(URL, container, chartType, options, colorData);
}

/**
 *
 * @param URL
 * @param container
 */
function pieChart(URL, container) {
    "use strict";

    var colorData = false;
    var options = $.extend(true, {}, defaultPieOptions);
    var chartType = 'pie';
    
    // Explicitly disable crosshair for pie charts (simple approach like PostHog)
    // Reference: https://github.com/PostHog/posthog/commit/a85f8b4bcb9de90cdb0ceb52186c358e9107855a
    options.plugins = options.plugins || {};
    options.plugins.crosshair = false;

    drawAChart(URL, container, chartType, options, colorData);

}

/**
 *
 * @param URL
 * @param container
 */
function multiCurrencyPieChart(URL, container) {
    "use strict";

    var colorData = false;
    var options = $.extend(true, {}, pieOptionsWithCurrency);
    var chartType = 'pie';
    
    // Debug: Check if crosshair configuration is present
    console.log('Pie chart options:', options);
    console.log('Pie chart crosshair config:', options.plugins && options.plugins.crosshair);

    // Create pie chart directly without going through drawAChart to avoid crosshair plugin
    $.getJSON(URL).done(function (data) {
        if (typeof data === 'undefined' || 0 === data.length || 
            (typeof data === 'object' && typeof data.labels === 'object' && 0 === data.labels.length)) {
            var holder = $('#' + container).parent().parent();
            if (holder.hasClass('box') || holder.hasClass('box-body')) {
                var boxBody;
                if (!holder.hasClass('box-body')) {
                    boxBody = holder.find('.box-body');
                } else {
                    boxBody = holder;
                }
                boxBody.empty().append($('<p>').append($('<em>').text(noDataForChart)));
            }
            return;
        }

        if (colorData) {
            data = colorizeData(data);
        }

        if (allCharts.hasOwnProperty(container)) {
            allCharts[container].data.datasets = data.datasets;
            allCharts[container].data.labels = data.labels;
            allCharts[container].update();
        } else {
            var ctx = document.getElementById(container).getContext("2d");
            var chartOpts = {
                type: chartType,
                data: data,
                options: options
            };
            
            // Debug: Check final chart options
            console.log('Pie chart final options:', chartOpts.options);
            console.log('Pie chart final crosshair config:', chartOpts.options.plugins && chartOpts.options.plugins.crosshair);
            
        // Explicitly disable crosshair for pie charts (simple approach like PostHog)
        // Reference: https://github.com/PostHog/posthog/commit/a85f8b4bcb9de90cdb0ceb52186c358e9107855a
        chartOpts.options.plugins = chartOpts.options.plugins || {};
        chartOpts.options.plugins.crosshair = false;
            console.log('Crosshair disabled for multi-currency pie chart:', container);
            
            // Destroy existing chart if it exists
            if (allCharts[container]) {
                allCharts[container].destroy();
            }
            
            allCharts[container] = new Chart(ctx, chartOpts);
        }
    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
}

/**
 * @param URL
 * @param container
 * @param chartType
 * @param options
 * @param colorData
 * @param today
 */
function drawAChart(URL, container, chartType, options, colorData) {
    var containerObj = $('#' + container);
    if (containerObj.length === 0) {
        return;
    }

    $.getJSON(URL).done(function (data) {
        console.log("Chart data received:", data);
        console.log("Chart labels:", data.labels);
        console.log("Chart datasets:", data.datasets);
        containerObj.removeClass('general-chart-error');

        // if result is empty array, or the labels array is empty, show error.
        // console.log(URL);
        // console.log(data.length);
        // console.log(typeof data.labels);
        // console.log(data.labels.length);
        if (
            // is undefined
            typeof data === 'undefined' ||
            // is empty
            0 === data.length ||
            // isn't empty but contains no labels
            (typeof data === 'object' && typeof data.labels === 'object' && 0 === data.labels.length)
        ) {
            // remove the chart container + parent
            var holder = $('#' + container).parent().parent();
            if (holder.hasClass('box') || holder.hasClass('box-body')) {
                // find box-body:
                var boxBody;
                if (!holder.hasClass('box-body')) {
                    boxBody = holder.find('.box-body');
                } else {
                    boxBody = holder;
                }
                boxBody.empty().append($('<p>').append($('<em>').text(noDataForChart)));
            }
            return;
        }

        if (colorData) {
            data = colorizeData(data);
        }

        if (allCharts.hasOwnProperty(container)) {
            // Apply conditional fill colors for line charts
            if (chartType === 'line' && data.datasets && data.datasets.length > 0) {
                var dataset = data.datasets[0];
                dataset.fill = 'origin';
                
                if (dataset.data && dataset.data.length > 0) {
                    var backgroundColor = [];
                    var borderColor = [];
                    
                    for (var i = 0; i < dataset.data.length; i++) {
                        var value = dataset.data[i];
                        if (value >= 0) {
                            backgroundColor.push('rgba(0, 255, 0, 0.15)'); // Green for positive
                            borderColor.push('rgba(0, 255, 0, 0.8)');
                        } else {
                            backgroundColor.push('rgba(255, 0, 0, 0.15)'); // Red for negative
                            borderColor.push('rgba(255, 0, 0, 0.8)');
                        }
                    }
                    
                    dataset.backgroundColor = backgroundColor;
                    dataset.borderColor = borderColor;
                }
            }
            
            allCharts[container].data.datasets = data.datasets;
            allCharts[container].data.labels = data.labels;
            allCharts[container].update();
        } else {
            // new chart!
            var ctx = document.getElementById(container).getContext("2d");
            console.log("Creating new chart with data:", data);
            console.log("Chart options:", options);
            var chartOpts = {
                type: chartType,
                data: data,
                options: options,
                lineAtIndex: [],
                annotation: {},
            };
            if (typeof drawVerticalLine !== 'undefined') {
                if (drawVerticalLine !== '') {
                    // draw line using annotation plugin.
                    chartOpts.options.annotation = {
                        annotations: [{
                            type: 'line',
                            id: 'a-line-1',
                            mode: 'vertical',
                            scaleID: 'x-axis-0',
                            value: drawVerticalLine,
                            borderColor: lineColor,
                            borderWidth: 1,
                            label: {
                                backgroundColor: 'rgba(0,0,0,0)',
                                fontFamily: "sans-serif",
                                fontSize: 12,
                                fontColor: lineTextColor,
                                position: "right",
                                xAdjust: -20,
                                yAdjust: -125,
                                enabled: true,
                                content: todayText
                            }
                        }]
                    };
                }
            }
            // Destroy existing chart if it exists
            if (allCharts[container]) {
                allCharts[container].destroy();
            }
            
            // Configure plugins per chart type
            // Reference: https://github.com/PostHog/posthog/commit/a85f8b4bcb9de90cdb0ceb52186c358e9107855a
            // This approach disables crosshair on non-line charts using crosshair: false
            chartOpts.plugins = chartOpts.plugins || [];
            chartOpts.options.plugins = chartOpts.options.plugins || {};
            
            if (chartType === 'line') {
                // Ensure tooltips are properly configured for line charts
                chartOpts.options.plugins.tooltip = chartOpts.options.plugins.tooltip || {};
                chartOpts.options.plugins.tooltip.enabled = true;
                chartOpts.options.plugins.tooltip.mode = 'index';
                chartOpts.options.plugins.tooltip.intersect = false;
                
                // Enable crosshair for line charts
                chartOpts.options.plugins.crosshair = {
                    enabled: true,
                    line: {
                        color: '#666',
                        width: 1,
                        dashPattern: [5, 5]
                    },
                    sync: {
                        enabled: false,
                        group: 1,
                        suppressTooltips: false
                    },
                    zoom: {
                        enabled: true,
                        zoomboxBackgroundColor: 'rgba(66,133,244,0.2)',
                        zoomboxBorderColor: '#48F',
                        zoomButtonText: 'Reset Zoom',
                        zoomButtonClass: 'reset-zoom'
                    },
                    snap: {
                        enabled: false
                    },
                    callbacks: {
                        beforeZoom: function(start, end) { return true; },
                        afterZoom: function(start, end) {}
                    }
                };
                console.log('Crosshair enabled for line chart:', container);
                console.log('Crosshair configuration:', chartOpts.options.plugins.crosshair);
                console.log('Tooltip configuration:', chartOpts.options.plugins.tooltip);
                
                // Add conditional fill colors for positive/negative values
                if (data.datasets && data.datasets.length > 0) {
                    var dataset = data.datasets[0];
                    
                    // Configure fill to zero line
                    dataset.fill = 'origin';
                    
                    // Add conditional background colors based on data values
                    if (dataset.data && dataset.data.length > 0) {
                        var backgroundColor = [];
                        var borderColor = [];
                        
                        for (var i = 0; i < dataset.data.length; i++) {
                            var value = dataset.data[i];
                            if (value >= 0) {
                                backgroundColor.push('rgba(0, 255, 0, 0.15)'); // Green for positive
                                borderColor.push('rgba(0, 255, 0, 0.8)');
                            } else {
                                backgroundColor.push('rgba(255, 0, 0, 0.15)'); // Red for negative
                                borderColor.push('rgba(255, 0, 0, 0.8)');
                            }
                        }
                        
                        dataset.backgroundColor = backgroundColor;
                        dataset.borderColor = borderColor;
                    }
                }
            } else {
                // Explicitly disable crosshair for other chart types (simple approach like PostHog)
                chartOpts.options.plugins.crosshair = false;
                console.log('Crosshair disabled for', chartType, 'chart:', container);
            }
            
            allCharts[container] = new Chart(ctx, chartOpts);
        }

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
}
