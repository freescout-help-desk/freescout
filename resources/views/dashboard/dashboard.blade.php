@extends('layouts.app')
@section('content')
<div class="container-fluid color" style="padding: 0 60px;margin-bottom: 3em;">
    <div class="filter">
        <div>
            <label for="ticket">Ticket Category</label>
            <select name="ticket" id="" ticket class="ticket" >
                <option value="none"></option>
                @foreach($categoryValues as $category)
                    <option value="{{$category}}">{{$category}}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="ticket">Product</label>
            <select name="ticket" id="" ticket class="ticket">
                <option value="none"></option>
                <option value="open">Product 1</option>
                <option value="hold">Product 2</option>
                <option value="closed">Product 3</option>
            </select>
        </div>
        <div>
            <label for="ticket">Type</label>
            <select name="ticket" id="" ticket class="ticket">
                <option value=""></option>
			<option value="{{ App\Conversation::TYPE_EMAIL }}">{{ __('Email') }}</option>
			<option value="{{ App\Conversation::TYPE_CHAT }}">{{ __('Chat') }}</option>
			<option value="{{ App\Conversation::TYPE_PHONE }}">{{ __('Phone') }}</option>
            </select>
        </div>
        <div>
            <label for="ticket">Mailbox</label>
            <select name="ticket" id="" ticket class="ticket">
                <option value=""></option>
			@foreach (Auth::user()->mailboxesCanView(true) as $mailbox)
				<option value="{{ $mailbox->id }}">{{ $mailbox->name }}</option>
			@endforeach
            </select>
        </div>
        <div style="display: flex; align-items: center;">
            <label for="date">Date</label>
            <input type="date" class="form-control date" id="date" >
        </div>

    </div>

    <div class="row text-center" style="margin-top: 6rem;">
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

    <div class="row text-center" style="margin-top: 4rem;">
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



    <div class="donut-container">
        <div class="donut-chart" >
            <div>
                <canvas id="donutChart" height="230px"></canvas>
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
                        <p class="donutp">Open tickets</p>
                        <p class="donutp">{{$unclosedCount}}</p>
                    </div>
                    <div class="col-sm-6">
                        <p class="donutp">Close tickets</p>
                        <p class="donutp">{{$closedCount}}</p>
                    </div>
                    <div class="col-sm-6">
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
    .dm .filter{
        border: 1px solid #eee;
        padding: 2rem;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dm hr{
        border: 1px solid #eee;
    }
     hr{
        border: 1px solid black;
    }
    .filter{
        border: 1px solid #9b9898;
        padding: 2rem;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dm.filter .ticket{
        background-color: transparent;
        padding: 3px;
        border-radius: 4px;
        margin-left: 4px;
        color:#1D1C24;
    }
    .filter .ticket{
        background-color: transparent;
        padding: 3px;
        border-radius: 4px;
        margin-left: 4px;
        color:#1D1C24;
        background: #f8f5f5;
    }
    .dm .filter .ticket:focus{
        background-color: transparent;
        padding: 3px;
        border-radius: 4px;
        margin-left: 4px;
        color:#1D1C24;
    }
    .filter .ticket:focus{
        background-color: transparent;
        padding: 3px;
        border-radius: 4px;
        margin-left: 4px;
        color:#1D1C24;
    }
    .dm .filter .ticket, .date{
        margin-left: 10px;
        padding: 3px;
        margin-bottom: 2px;
        border-radius: 4px;
        color:#000;
        background: #303d71;
    }
    .dm .filter .ticket, .date :visited{
        color:snow;
    }
    .dm .date{
        background-color: #303d71 !important;
        color: #f8f5f5;
    }
    .filter .ticket, #date{
        margin-left: 10px;
        padding: 3px;
        margin-bottom: 2px;
        border-radius: 4px;
        color:#000;
        background: #f8f5f5;
    }
    .dm .donut-container .donut-chart{
        display: flex;
        flex:1;
        align-items:center;
         gap: 5rem;
         background: #1D1C24;
         padding: 4px;
         border-radius: 4px;
          height:350px
    }
    .donut-container .donut-chart{
        display: flex;
        flex:1;
        align-items:center;
         gap: 5rem;
         background:#eeeeee;
         padding: 4px;
         border-radius: 4px;
          height:350px
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
        height:350px
    }
    .horizontalChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #eeeeee;
        padding: 4px;
        border-radius: 4px;
        height:350px
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
    }
    .dm .bar-container .lineChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #1D1C24;
        padding: 4px;
        border-radius: 4px;
        height:350px
    }
    .bar-container .lineChart{
        display: flex;
        flex:1;
        align-items:center;
        gap: 7rem;
        background: #eeeeee;
        padding: 4px;
        border-radius: 4px;
        height:350px
    }
    input, button, select, textarea{
        color: #1D1C24
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
            backgroundColor: ['#89F81B', '#7831F9', '#173292'],
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
</script>

@endsection
