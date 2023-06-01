@extends('layouts.app')
@section('content')
<form action="{{ route('filter') }}" method="GET" class="top-form">
        <div class="rpt-filters">

            {{-- <div class="rpt-views-trigger">
                @include('reports::partials/views')
            </div> --}}

            <div class="rpt-filter">
                {{ __('Tickets Category') }}
                <select class="form-control" name="ticket" >
                    <option value=""></option>
                    @foreach ($categoryValues as $category)
                        <option value="{{$category}}">{{$category}}</option>
                    @endforeach
                </select>
            </div>
            <div class="rpt-filter">
                {{ __('Product') }}
                <select class="form-control" name="product">
                    <option value=""></option>
                    @foreach ($productValues as $product)
                        <option value="{{$product}}">{{$product}}</option>
                    @endforeach
                </select>
            </div>
            <div class="rpt-filter">
                {{ __('Type') }}
                <select class="form-control" name="type">
                    <option value=""></option>
                    <option value="{{ App\Conversation::TYPE_EMAIL }}">{{ __('Email') }}</option>
                    <option value="{{ App\Conversation::TYPE_CHAT }}">{{ __('Chat') }}</option>
                    <option value="{{ App\Conversation::TYPE_PHONE }}">{{ __('Phone') }}</option>
                </select>
            </div>
            <div class="rpt-filter">
                {{ __('Mailbox') }}
                <select class="form-control" name="mailbox">
                    <option value=""></option>
                    @foreach (Auth::user()->mailboxesCanView(true) as $mailbox)
                        <option value="{{ $mailbox->id }}">{{ $mailbox->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rpt-filter">
                <nobr><input type="date" name="from" class="form-control rpt-filter-date" value="{{ $filters['from'] }}" />-<input type="date" name="to" class="form-control rpt-filter-date" value="{{ $filters['to'] }}" /></nobr>
                {{-- <button class="btn btn-primary" name="period">Oct 1, 2017 - Nov 1, 2017 <span class="caret"></span></button> --}}
            </div>

            <div class="rpt-filter" data-toggle="tooltip" title="{{ __('Refresh') }}">
                <button class="btn btn-primary" id="rpt-btn-loader" onclick="refreshPage()" type="submit"><i class="glyphicon glyphicon-refresh"></i></button>
            </div>

        </div>

</form>
<div class="container-fluid color" style="padding: 0 20px;margin-bottom: 3em;">
    <div class="row text-center" style="margin-top: 6rem;">
       <div class="row-text-center1">
       <div class="col-md-4">
            <p class="stat-options">Total Tickets</p>
            <h1 class="stat-values">{{$totalCount}}</h1>
        </div>
        <div class="col-md-4">
            <p class="stat-options">Unassigned Tickets</p>
            <h1 class="stat-values">{{$unassignedCount}}</h1>
        </div>
        <div class="col-md-4">
            <p class="stat-options">Overdue Tickets</p>
            <h1 class="stat-values">{{$overdueCount}}</h1>
        </div>
       </div>
    </div>

    <div class="row text-center" style="margin-top: 0rem;">
       <div class="row-text-center1">
       <div class="col-md-4">
            <p class="stat-options">Open Tickets</p>
            <h1 class="stat-values">{{$unclosedCount}}</h1>
        </div>
        <div class="col-md-4">
            <p class="stat-options">Close Tickets</p>
            <h1 class="stat-values">{{$closedCount}}</h1>
        </div>
        <div class="col-md-4">
            <p class="stat-options">Hold Tickets</p>
            <h1 class="stat-values">{{$unclosedCreated30DaysAgoCount}}</h1>
        </div>
       </div>
    </div>



    <div class="donut-container">
        <div class="donut-chart" >
            <div>
                <canvas id="donutChart" height="230px" width="100%"></canvas>
            </div>
            <div>
                <div class="donut-chart-lable">
                    <div  class="donut-chart-box"></div>
                    <div>
                        <p class="donutp">Number Of Tickets</p>
                        <p class="donutp">{{$totalCount}}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6">
                        <span class="circle circle-green"></span>
                        <p class="donutp">Open tickets</p>
                        <p class="donutp">{{$unclosedCount}}</p>
                    </div>
                    <div class="col-sm-6">
                        <span class="circle circle-red"></span>
                        <p class="donutp">Close tickets</p>
                        <p class="donutp">{{$closedCount}}</p>
                    </div>
                    <div class="col-sm-6">
                        <span class="circle circle-blue"></span>
                        <p class="donutp">Hold tickets</p>
                        <p class="donutp">{{$unclosedCreated30DaysAgoCount}}</p>
                    </div>
                </div>
            </div>
        </div>

          <div class="horizontalChart">
            <canvas id="horizontalChart" ></canvas>
          </div>
    </div>

    <div class="bar-container">
        <div class="barChart">
                <canvas id="barChart"></canvas>
        </div>
        <div class="lineChart">
            <canvas id="lineChart" ></canvas>
          </div>
    </div>
</div>
<style>
.content{
    margin-top: 0;
}
.top-form{
    display: flex;
    height: 4em;
    align-items: center;
    justify-content: space-evenly;
    background: #deecf9;
}

.dm .top-form{
    background: #005eb4;
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

.stat-options{
      /* color: hotpink; */
}
.dm .form-control {
    display: inline ;
    width: 140px;
    min-inline-size: max-content;
}

.form-control {
    display: inline ;
    width: 140px;
    min-inline-size: max-content;
}

    .dm hr{
        border: 1px solid #eee;
    }
     hr{
        border: 1px solid black;
    }
    .dm .donut-container .donut-chart{
        display: flex;
        flex:1;
        align-items:center;
         gap: 5rem;
         background: #1D1C24;
         padding: 4px;
         border-radius: 4px;
          height:350px;
          width: 100%;
    }
    .donut-container .donut-chart{
        display: flex;
        flex:1;
        align-items:center;
         gap: 5rem;
         background:#eeeeee;
         padding: 4px;
         border-radius: 4px;
          height:350px;
          width: 100%;
    }
    .donut-container .donut-chart .donut-chart-lable{
        display: flex;
        flex:1;
        align-items:center;
        gap: 5px;
    }
    .donut-container .donut-chart .donut-chart-box{
        background-color: plum;
        height: 40px;
        width: 40px;
        border-radius: 4px;

    }
    .donutp{
        display: table-header-group;
    }
    .dm .horizontalChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #1D1C24;
        padding: 4px;
        border-radius: 4px;
        height:350px;
        width: 100%;
    }
    .horizontalChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #eeeeee;
        padding: 4px;
        border-radius: 4px;
        height:350px;
        width: 100%;
    }
    .dm .bar-container .barChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #1D1C24;
        padding: 4px;
        border-radius: 4px;
        height:350px;
        justify-content:center;
        width: 100%;
    }
    .bar-container .barChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #eeeeee;
        padding: 4px;
        border-radius: 4px;
        height:350px;
        justify-content:center;
        width: 100%;
    }
    .dm .bar-container .lineChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #1D1C24;
        padding: 4px;
        border-radius: 4px;
        height:350px;
        width: 100%;
    }
    .bar-container .lineChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #eeeeee;
        padding: 4px;
        border-radius: 4px;
        height:350px;
        width: 100%;
    }
    input, button, select, textarea{
        color: #1D1C24
    }


    /* its my code for external coonent*/
    .dm .donut-container{
        max-width:100%;
        display: flex;
        flex:50%;

    }
    .donut-container{
        max-width:100%;
        display: flex;
        flex:50%;
    }


    .dm .bar-container{
        max-width:100%;
        display: flex;
        flex:50%;
    }
    .bar-container{
        display: flex;
        flex:50%;
        max-width:100%;
    }

     /* its my code for external coonent*/

    @media (max-width: 600px) {
        .dm .donut-container{
        display: flex;
        flex-direction:column;
    }
    .donut-container{
        display: flex;
        flex-direction:column;
        
    }


    .dm .bar-container{
        display: flex;
        flex-direction:column;
    }
    .bar-container{
        display: flex;
        flex-direction:column;
    }
    

    .row-text-center1{
        display:flex;
        flex-direction:row;
        margin-left:0px;
    }


    .stat-options {
        font-size: 15px;
        max-width: 100%;
        margin-top:20px;
    }
    .stat-values{
        font-size: 15px;
        max-width: 100%;
    }

    .donut-container{
        margin-top: 3rem;
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
            data: ["{{ $unclosedCount}}", "{{$closedCount}}", "{{$unclosedCreated30DaysAgoCount}}"],
            backgroundColor: ['#89F81B', 'red', '#173292'],
            // backgroundColor: ['red', 'blue', 'yellow'],
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
            const options = { weekday: 'long' }; // Specify the format of the weekday
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
        const weeklyBarChart = ["{{$tickets['Sunday']}}", "{{$tickets['Monday']}}", "{{$tickets['Tuesday']}}", "{{$tickets['Wednesday']}}", "{{$tickets['Thursday']}}", "{{$tickets['Friday']}}", "{{$tickets['Saturday']}}"];

        for (let i = 6; i >= 0; i--) {
            const day = new Date(currentDate);
            day.setDate(day.getDate() - i);
            const options = { weekday: 'long' }; // Specify the format of the weekday
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
                data: ["{{$tickets['Sunday']}}", "{{$tickets['Monday']}}", "{{$tickets['Tuesday']}}", "{{$tickets['Wednesday']}}", "{{$tickets['Thursday']}}", "{{$tickets['Friday']}}", "{{$tickets['Saturday']}}"],
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

});
function refreshPage() {
    location.reload();
}
</script>

@endsection
