/**
 * Module's JavaScript.
 */

function initReports()
{
	$(document).ready(function(){
		rptRefresh();

		// Dates
		$('#rpt-filters .input-date').flatpickr({allowInput: true});

		$('#rpt-btn-loader').click(function(e) {
			rptRefresh();
		});

		$('.rpt-filter :input').change(function(e) {
			rptRefresh();
		});

		$('.rpt-filter-date').flatpickr();

		$('.rpt-filter select[name="mailbox"]:first').change(function(e) {
			$('.rpt-cf-mailbox').addClass('hidden');
			$('.rpt-cf-mailbox-'+$(this).val()).removeClass('hidden');
		});
	});
}

function rptRefresh()
{
	var data = {};

	var loader = $('#rpt-btn-loader');

	data.action = 'report';
	data.report_name = $('#rpt-report').attr('data-report-name');
	data.filters = {};
	var filters = $('#rpt-filters').serializeArray();
	for (var i in filters) {
		var filter = filters[i];
		var m = filter.name.match(/^custom_field\[(\d+)\]$/);
		if (m && typeof(m[1]) != "undefined") {
			// Miss hidden custom fields
			if (!$('.rpt-filter :input[name="'+filter.name+'"]').parent().is(":visible")) {
				continue;
			}
			if (typeof(data.filters['custom_field']) == "undefined") {
				data.filters['custom_field'] = {};
			}
			data.filters['custom_field'][m[1]] = filter.value;
		} else {
			data.filters[filter.name] = filter.value;
		}
	}
	data.chart = {};
	data.chart.group_by = $('#rpt-group-by button.active').val();
	data.chart.type = $('#rpt-chart-type').val();

	fsDoAction('reports.before_refresh', {data: data});

	loader.attr('disabled', 'disabled').children('.glyphicon:first').addClass('glyphicon-spin');
	$('.rpt-filter :input').attr('disabled', 'disabled');
	$('#rpt-options :input').attr('disabled', 'disabled');

	fsAjax(data, 
		laroute.route('reports.ajax'), 
		function(response) {
			if (isAjaxSuccess(response) && response.report) {
				$('#rpt-report').html(response.report);
				var chart_type = $('#rpt-report').attr('data-chart-type');
				if (chart_type == 'pie') {
					rptShowChartPie(response.chart);
				} else if (chart_type == 'column') {
					rptShowChartColumn(response.chart);
				} else {
					rptShowChart(response.chart);
				}
				initTooltips();

				$('#rpt-group-by button').click(function(e) {
					if ($(this).hasClass('active')) {
						return;
					}
					$('#rpt-group-by button').removeClass('active');
					$(this).addClass('active');
					rptRefresh();
				});
				$('#rpt-chart-type').change(function(e) {
					rptRefresh();
				});
			} else {
				showAjaxResult(response);
			}
			loader.removeAttr('disabled').children('.glyphicon:first').removeClass('glyphicon-spin');
			$('.rpt-filter :input').removeAttr('disabled');
			$('#rpt-options :input').removeAttr('disabled');
		}, true,
		function(response) {
			showAjaxResult(response);
			loader.removeAttr('disabled').children('.glyphicon:first').removeClass('glyphicon-spin');
			$('.rpt-filter :input').removeAttr('disabled');
			$('#rpt-options :input').removeAttr('disabled');
		}
	);
}

function rptShowChart(data)
{
	Highcharts.chart('rpt-chart', {
	    chart: {
	        type: 'area'
	    },
	    /*accessibility: {
	        description: ''
	    },*/
	    title: {
	        text: ''
	    },
		legend: {
	        
	        //align: 'left',
	        verticalAlign: 'top',
	        floating: true
	    },
	    /*subtitle: {
	        text: ''
	    },*/
	    xAxis: {
			/*type: 'datetime',
	        labels: {
	            format: '{value:'+data.date_format+'}'
	            /*rotation: 45,
	            align: 'left'* /
	        },*/
	        categories: data.categories,
	        /*allowDecimals: false,
	        labels: {
	            formatter: function () {
	                return this.value; // clean, unformatted number for year
	            }
	        },
	        accessibility: {
	            rangeDescription: 'Range: 1940 to 2017.'
	        }*/
	    },
	    yAxis: {
	        title: {
	            text: ' '
	        }
	        /*labels: {
	            formatter: function () {
	                return this.value / 1000 + 'k';
	            }
	        }*/
	    },
	    tooltip: {
	    	shared: true
	        //pointFormat: '{series.name} had stockpiled <b>{point.y:,.0f}</b><br/>warheads in {point.x}'
	    },
	    plotOptions: {
	        area: {
	            //pointStart: 1940,
	            //fillColor: 'rgba(68, 170, 213, .2)',
	            //fillOpacity: 0.5,
	            marker: {
	                enabled: false,
	                symbol: 'circle',
	                radius: 2,
	                states: {
	                    hover: {
	                        enabled: true
	                    }
	                }
	            }
	        }
	    },
	    series: data.series
	    /*series: [{
	        name: 'Current',
	        data: [
	            null, null, null, null, null, 6, 11, 32, 110, 235,
	            369, 640, 1005, 1436, 2063, 3057, 4618, 6444, 9822, 15468,
	            20434, 24126, 27387, 29459, 31056, 31982, 32040, 31233, 29224, 27342,
	            26662, 26956, 27912, 28999, 28965, 27826, 25579, 25722, 24826, 24605,
	            24304, 23464, 23708, 24099, 24357, 24237, 24401, 24344, 23586, 22380,
	            21004, 17287, 14747, 13076, 12555, 12144, 11009, 10950, 10871, 10824,
	            10577, 10527, 10475, 10421, 10358, 10295, 10104, 9914, 9620, 9326,
	            5113, 5113, 4954, 4804, 4761, 4717, 4368, 4018
	        ]
	    }, {
	        name: 'Previous',
	        data: [null, null, null, null, null, null, null, null, null, null,
	            5, 25, 50, 120, 150, 200, 426, 660, 869, 1060,
	            1605, 2471, 3322, 4238, 5221, 6129, 7089, 8339, 9399, 10538,
	            11643, 13092, 14478, 15915, 17385, 19055, 21205, 23044, 25393, 27935,
	            30062, 32049, 33952, 35804, 37431, 39197, 45000, 43000, 41000, 39000,
	            37000, 35000, 33000, 31000, 29000, 27000, 25000, 24000, 23000, 22000,
	            21000, 20000, 19000, 18000, 18000, 17000, 16000, 15537, 14162, 12787,
	            12600, 11400, 5500, 4512, 4502, 4502, 4500, 4500
	        ]
	    }]*/
	});
}

function rptShowChartPie(data)
{
	Highcharts.chart('rpt-chart', {
	    chart: {
	    	plotBackgroundColor: null,
	        plotBorderWidth: null,
	        plotShadow: false,
	        type: 'pie'
	    },
	    title: {
	        text: ''
	    },
	    tooltip: {
	        pointFormat: '<b>{point.y}%</b>'
	    },
	 	accessibility: {
	        point: {
	            valueSuffix: '%'
	        }
	    },
	    plotOptions: {
	    	series: {
				animation: false
			},
	        pie: {
	            allowPointSelect: true,
	            cursor: 'pointer',
	            dataLabels: {
	                enabled: true,
	                format: '<b>{point.name}</b>: {point.y} %'
	            }
	        }
	    },
	    series: data.series
	    /*series: [{
	        data: [{
	            name: 'Good',
	            y: 61.41,
	            selected: true,
	            color: 'rgb(83,185,97)'
	        }, {
	            name: 'Okay',
	            y: 11.84,
	            color: 'rgb(147,161,175)'
	        }, {
	            name: 'Not Good',
	            y: 10.85,
	            color: 'rgb(212,63,58)'
	        }]
	    }]*/
	});
}

function rptShowChartColumn(data)
{
	Highcharts.chart('rpt-chart', {
	    chart: {
	        type: 'column'
	    },
	    title: {
	        text: ''
	    },
	    tooltip: {
	        pointFormat: '<b>{point.y}</b>'
	    },
	 	plotOptions: {
	        column: {
	            pointPadding: 0.2,
	            borderWidth: 0
	        }
	    },
	    yAxis: {
	        min: 0,
	        title: {
	            text: data.y_title
	        }
	    },
		xAxis: {
	        categories: data.categories,
	        crosshair: true
	    },
	    series: data.series
	});
}