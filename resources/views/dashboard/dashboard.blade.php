@extends('layouts.app')
@section('content')
    <i class="glyphicon glyphicon-filter filter-trigger"></i>
    <div class="rpt-header">
        <form action="{{ route('filter') }}" method="GET" class="top-form">
            <div class="rpt-filters">


                <div class="rpt-filter">
                    <label>
                        {{ __('Tickets Category') }}
                    </label>
                    <select class="form-control" name="ticket">
                        <option value="0">All</option>
                        @foreach ($categoryValues as $category)
                            <option value="{{ $category }}" {{ $filters['ticket'] === $category ? 'selected' : '' }}>
                                {{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rpt-filter">
                    <label>
                        {{ __('Product') }}
                    </label>
                    <select class="form-control" name="product">
                        <option value="0">All</option>
                        @foreach ($productValues as $product)
                            <option value="{{ $product }}" {{ $filters['product'] === $product ? 'selected' : '' }}>
                                {{ $product }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rpt-filter">
                    <label>
                        {{ __('Type') }}
                    </label>
                    <select class="form-control" name="type">
                        <option value="0">All</option>
                        <option value="{{ App\Conversation::TYPE_EMAIL }}"
                            {{ $filters['type'] == App\Conversation::TYPE_EMAIL ? 'selected' : '' }}>{{ __('Email') }}
                        </option>
                        <option value="{{ App\Conversation::TYPE_CHAT }}"
                            {{ $filters['type'] == App\Conversation::TYPE_CHAT ? 'selected' : '' }}>{{ __('Chat') }}
                        </option>
                        <option value="{{ App\Conversation::TYPE_PHONE }}"
                            {{ $filters['type'] == App\Conversation::TYPE_PHONE ? 'selected' : '' }}>{{ __('Phone') }}
                        </option>
                    </select>
                </div>
                <div class="rpt-filter">
                    <label>
                        {{ __('Mailbox') }}
                    </label>
                    <select class="form-control" name="mailbox">
                        <option value="0">All</option>
                        @foreach (Auth::user()->mailboxesCanView(true) as $mailbox)
                            <option value="{{ $mailbox->id }}"
                                {{ $filters['mailbox'] == $mailbox->id ? 'selected' : '' }}>{{ $mailbox->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rpt-filter">
                    <label class="mobile-label">{{ __('Date Range') }}</label>
                    <nobr><input type="date" name="from" class="form-control rpt-filter-date"
                            value="{{ $filters['from'] }}" />-<input type="date" name="to"
                            class="form-control rpt-filter-date" value="{{ $filters['to'] }}" /></nobr>
                </div>

                <div class="rpt-filter" data-toggle="tooltip" title="{{ __('Refresh') }}">
                    <button class="btn btn-primary" id="rpt-btn-loader" onclick="refreshPage()" type="submit"><i
                            class="glyphicon glyphicon-refresh"></i></button>
                </div>

            </div>

        </form>
    </div>
    <div class="container-fluid color" style="padding: 0 20px;margin-bottom: 3em;">
        <div class="row text-center" style="margin-top: 6rem;">
            <div class="row-text-center1">
                <div class="col-md-4">
                    <p class="stat-options">Total Tickets</p>
                    <h1 class="stat-values">{{ $totalCount }}</h1>
                </div>
                <div class="col-md-4">
                    <p class="stat-options">Unassigned Tickets</p>
                    <h1 class="stat-values">{{ $unassignedCount }}</h1>
                </div>
                <div class="col-md-4">
                    <p class="stat-options">Overdue Tickets</p>
                    <h1 class="stat-values">{{ $overdueCount }}</h1>
                </div>
            </div>
        </div>

        <div class="row text-center" style="margin-top: 0rem;">
            <div class="row-text-center1">
                <div class="col-md-4">
                    <p class="stat-options">Open Tickets</p>
                    <h1 class="stat-values">{{ $unclosedCount }}</h1>
                </div>
                <div class="col-md-4">
                    <p class="stat-options">Close Tickets</p>
                    <h1 class="stat-values">{{ $closedCount }}</h1>
                </div>
                <div class="col-md-4">
                    <p class="stat-options">Hold Tickets</p>
                    <h1 class="stat-values">{{ $unclosedCreated30DaysAgoCount }}</h1>
                </div>
            </div>
        </div>



        <div class="donut-container">
            <div class="donut-chart">
                <div class="col-md-6">
                    <canvas id="donutChart" height="230px" width="100%"></canvas>
                </div>
                <div>
                    <div class="donut-chart-lable">
                        <div class="donut-chart-box"></div>
                        <div>
                            <p class="donutp">Number Of Tickets</p>
                            <p class="donutp">{{ $totalCount }}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-6">
                            <span class="circle circle-green"></span>
                            <p class="donutp">Open tickets</p>
                            <p class="donutp">{{ $unclosedCount }}</p>
                        </div>
                        <div class="col-sm-6">
                            <span class="circle circle-red"></span>
                            <p class="donutp">Close tickets</p>
                            <p class="donutp">{{ $closedCount }}</p>
                        </div>
                        <div class="col-sm-6">
                            <span class="circle circle-blue"></span>
                            <p class="donutp">Hold tickets</p>
                            <p class="donutp">{{ $unclosedCreated30DaysAgoCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="horizontalChart">
                <canvas id="horizontalChart"></canvas>
            </div>
        </div>

        <div class="bar-container">
            <div class="barChart">
                <canvas id="barChart"></canvas>
            </div>
            <div class="lineChart">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>
    <style>
        .content {
            margin-top: 0;
        }

        .top-form {
            display: flex;
            height: auto;
            align-items: center;
            justify-content: space-evenly;
            background: #deecf9;
        }

        .dm .top-form {
            background: transparent;
        }

        .dm .rpt-header {
            background-color: transparent;
        }

        .rpt-header {
            box-shadow: -3px 13px 19px -2px #00000057;
        }

        .opn-menu {
            padding: 12px 18px;
            width: 75%;
            left: 25%;
        }

        .circle {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
            position: relative;
            top: 22px;
            left: -15px;
        }

        .circle-green {
            background-color: #89F81B;
        }

        .circle-red {
            background-color: red;
        }

        .circle-blue {
            background-color: #173292;
        }

        .dm .form-control {
            display: inline;
            width: 140px;
            min-inline-size: max-content;
        }

        .form-control {
            display: inline;
            width: 140px;
            min-inline-size: max-content;
        }

        .dm hr {
            border: 1px solid #eee;
        }

        hr {
            border: 1px solid black;
        }

        .dm .donut-container .donut-chart {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 5rem;
            background: #1D1C24;
            padding: 4px;
            border-radius: 4px;
            height: 350px;
            width: 100%;
        }

        .donut-container .donut-chart {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 5rem;
            background: #eeeeee;
            padding: 4px;
            border-radius: 4px;
            height: 350px;
            width: 100%;
        }

        .donut-container .donut-chart .donut-chart-lable {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 5px;
        }

        .donut-container .donut-chart .donut-chart-box {
            background-color: plum;
            height: 40px;
            width: 40px;
            border-radius: 4px;

        }

        .donutp {
            display: table-header-group;
        }

        .dm .horizontalChart {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 7rem;
            background: #1D1C24;
            padding: 4px;
            border-radius: 4px;
            height: 350px;
            width: 100%;
        }

        .horizontalChart {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 7rem;
            background: #eeeeee;
            padding: 4px;
            border-radius: 4px;
            height: 350px;
            width: 100%;
        }

        .dm .bar-container .barChart {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 7rem;
            background: #1D1C24;
            padding: 4px;
            border-radius: 4px;
            height: 350px;
            justify-content: center;
            width: 100%;
        }

        .bar-container .barChart {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 7rem;
            background: #eeeeee;
            padding: 4px;
            border-radius: 4px;
            height: 350px;
            justify-content: center;
            width: 100%;
        }

        .dm .bar-container .lineChart {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 7rem;
            background: #1D1C24;
            padding: 4px;
            border-radius: 4px;
            height: 350px;
            width: 100%;
        }

        .bar-container .lineChart {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 7rem;
            background: #eeeeee;
            padding: 4px;
            border-radius: 4px;
            height: 350px;
            width: 100%;
        }

        input,
        button,
        select,
        textarea {
            color: #1D1C24
        }


        /* its my code for external coonent*/
        .dm .donut-container {
            max-width: 100%;
            display: flex;
            flex: 50%;

        }

        .donut-container {
            max-width: 100%;
            display: flex;
            flex: 50%;
        }


        .dm .bar-container {
            max-width: 100%;
            display: flex;
            flex: 50%;
        }

        .bar-container {
            display: flex;
            flex: 50%;
            max-width: 100%;
        }

        .mobile-label {
            display: none;
        }

        .filter-trigger {
            display: none;
        }

        /* its my code for external coonent*/
        /**
         * Update: 06/06/23
         * I am over-writing your code
          */

        @media (max-width: 600px) {
            .dm .donut-container {
                display: flex;
                flex-direction: column;
            }

            .donut-container {
                display: flex;
                flex-direction: column;

            }

            .rpt-filter {
                display: flex;
                flex-direction: column;
                margin-bottom: 1.5em;
            }

            .rpt-header {
                position: absolute;
                z-index: 9;
                height: 100vh;
                width: 0;
                transition: 200ms;
                padding: 0;
                left: 100%;
                box-shadow: -3px 13px 19px -2px #00000057;
            }

            .opn-menu {
                padding: 12px 18px;
                width: 75%;
                left: 25%;
            }

            .mobile-label {
                display: block;
            }


            .dm .bar-container {
                display: flex;
                flex-direction: column;
            }

            .bar-container {
                display: flex;
                flex-direction: column;
            }


            .row-text-center1 {
                display: flex;
                flex-direction: row;
                margin-left: 0px;
            }


            .stat-options {
                font-size: 15px;
                max-width: 100%;
                margin-top: 20px;
            }

            .stat-values {
                font-size: 35px;
                max-width: 100%;
            }

            .donut-container {
                margin-top: 3rem;
            }

            .filter-trigger {
                display: block;
                color: #0078d7;
                font-size: 15px;
                position: absolute;
                right: 0;
                background: white;
                padding: 0.5em;
                top: 50px;
                box-shadow: 4px 2px 8px 1px #0000008f;
                border: 1px solid #0078d7;
                transition: 200ms;
            }

            .opn-filter {
                right: 75%;
            }

        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the canvas element
            var ctx = document.getElementById('donutChart').getContext('2d');

            // Set chart data
            var data = {
                datasets: [{
                    data: ["{{ $unclosedCount }}", "{{ $closedCount }}",
                        "{{ $unclosedCreated30DaysAgoCount }}"
                    ],
                    backgroundColor: ['#89F81B', 'red', '#173292'],
                    borderColor: 'transparent',
                }]
            };

            // Set chart options
            var options = {
                responsive: true,
                maintainAspectRatio: false,
                cutoutPercentage: 70
            };

            // Create the donut chart
            var donutChart = new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: options
            });

            // Linechart

            // Get the canvas element
            var ctxLine = document.getElementById('lineChart').getContext('2d');

            // Define the chart data
            const currentDate = new Date();
            const weekNames = [];

            for (let i = 6; i >= 0; i--) {
                const day = new Date(currentDate);
                day.setDate(day.getDate() - i);
                const options = {
                    weekday: 'long'
                }; // Specify the format of the weekday
                const weekName = new Intl.DateTimeFormat('en-US', options).format(day);
                weekNames.push(weekName);
            }

            var chartData = {
                labels: weekNames,
                datasets: [{
                    label: 'Average Time Taken To Close Within SLA',
                    data: [10, 20, 15, 25, 30, 10, 20],
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }]
            };

            // Create the chart
            var lineChart = new Chart(ctxLine, {
                type: 'line',
                data: chartData,
            });

            // Bar Chart

            var ctxBar = document.getElementById('barChart').getContext('2d');
            // Set chart data
            const weeklyBarChart = ["{{ $tickets['Sunday'] }}", "{{ $tickets['Monday'] }}",
                "{{ $tickets['Tuesday'] }}", "{{ $tickets['Wednesday'] }}", "{{ $tickets['Thursday'] }}",
                "{{ $tickets['Friday'] }}", "{{ $tickets['Saturday'] }}"
            ];

            for (let i = 6; i >= 0; i--) {
                const day = new Date(currentDate);
                day.setDate(day.getDate() - i);
                const options = {
                    weekday: 'long'
                }; // Specify the format of the weekday
                const weekName = new Intl.DateTimeFormat('en-US', options).format(day);
                weekNames.push(weekName);
            }

            var data = {
                labels: weekNames,
                datasets: [{
                    label: 'Average resolved tickets',
                    data: weeklyBarChart,
                    backgroundColor: '#2EA5FB', // Bar color
                    borderColor: 'rgba(54, 162, 235, 1)', // Border color
                    borderWidth: 1 // Border width
                }]
            };

            // Set chart options
            var options = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        stepSize: 4,
                    }
                }
            };

            // Create the bar chart
            var barChart = new Chart(ctxBar, {
                type: 'bar',
                data: data,
                options: options
            });

            // Horizontal Bar Data

            var ctxHorizontal = document.getElementById('horizontalChart').getContext('2d');
            // Set chart data
            let cValues = @json($categoryValues);
            var data = {
                labels: [...cValues],
                datasets: [{
                    label: 'Average resolved tickets',
                    data: ["{{ $tickets['Sunday'] }}", "{{ $tickets['Monday'] }}",
                        "{{ $tickets['Tuesday'] }}", "{{ $tickets['Wednesday'] }}",
                        "{{ $tickets['Thursday'] }}", "{{ $tickets['Friday'] }}",
                        "{{ $tickets['Saturday'] }}"
                    ],
                    backgroundColor: '#2EA5FB', // Bar color
                    borderColor: 'rgba(54, 162, 235, 1)', // Border color
                    borderWidth: 1 // Border width
                }]
            };

            // Set chart options
            var options = {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        stepSize: 4,
                    }
                }
            };

            // Create the bar chart
            var barChart = new Chart(ctxHorizontal, {
                type: 'bar',
                data: data,
                options: options
            });

            $(document).on('click', '.filter-trigger', function() {
                $('.rpt-header').toggleClass('opn-menu');
                $('.filter-trigger').toggleClass('opn-filter');
                if ($('.filter-trigger').hasClass('glyphicon-remove')) {
                    $('.filter-trigger').removeClass('glyphicon-remove');
                    $('.filter-trigger').addClass('glyphicon-filter');
                } else {
                    $('.filter-trigger').addClass('glyphicon-remove');
                    $('.filter-trigger').removeClass('glyphicon-filter');
                }
            });

        });

        function refreshPage() {
            location.reload();
        }
    </script>
@endsection
