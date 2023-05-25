@extends('layouts.app')
@section('content')
<div class="container-fluid color" style="padding: 0 60px;margin-bottom: 3em;">
    <div style="border: 2px solid #eee; padding: 2rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <label for="ticket">Ticket Category</label>
            <select name="ticket" id="" ticket style="background-color: transparent; border-radius: 4px; margin-left: 4px; color:#1D1C24;">
                <option value="none"></option>
                @foreach($categoryValues as $category)
                    <option value="{{$category}}">{{$category}}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="ticket">Product</label>
            <select name="ticket" id="" ticket style="background-color: transparent; border-radius: 4px; margin-left: 4px; color:#1D1C24;">
                <option value="none"></option>
                <option value="open">Product 1</option>
                <option value="hold">Product 2</option>
                <option value="closed">Product 3</option>
            </select>
        </div>
        <div>
            <label for="ticket">Type</label>
            <select name="ticket" id="" ticket style="background-color: transparent; border-radius: 4px; margin-left: 4px; color:#1D1C24;">
                <option value="none"></option>
                <option value="open">Type 1</option>
                <option value="hold">Type 2</option>
                <option value="closed">Type 3</option>
            </select>
        </div>
        <div>
            <label for="ticket">Mailbox</label>
            <select name="ticket" id="" ticket style="background-color: transparent; border-radius: 4px; margin-left: 4px; color:#1D1C24;">
                <option value="none"></option>
                <option value="open">Mailbox 1</option>
                <option value="hold">Mailbox 2</option>
                <option value="closed">Mailbox 3</option>
            </select>
        </div>
        <div style="display: flex; align-items: center;">
            <label for="date">Date</label>
            <input type="date" class="form-control" id="date" style="margin-left: 10px; margin-bottom: 2px; border-radius: 4px; color:snow;">
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
        <div style="display: flex; flex:1; align-items:center; gap: 5rem; background: #1D1C24;padding: 4px; border-radius: 4px; height:350px">
            <div>
                <canvas id="donutChart" height="230px"></canvas>
            </div>
            <div>
                <div style="display: flex;flex:1; align-items:center; gap: 5px;">
                    <div style="background-color: plum; height: 20px; width: 20px; border-radius: 4px;"></div>
                    <div>
                        <p>Number Of Tickets</p>
                        <p>{{$totalCount}}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6">
                        <p>Open tickets</p>
                        <p>{{$unclosedCount}}</p>
                    </div>
                    <div class="col-sm-6">
                        <p>Close tickets</p>
                        <p>{{$closedCount}}</p>
                    </div>
                    <div class="col-sm-6">
                        <p>Hold tickets</p>
                        <p>{{$unclosedCreated30DaysAgoCount}}</p>
                    </div>
                </div>
            </div>
        </div>

          <div style="display: flex; flex:1; align-items:center; gap: 7rem; background: #1D1C24;padding: 4px; border-radius: 4px; height:350px">
            <canvas id="horizontalChart" ></canvas>
          </div>
    </div>

    <div class="bar-container">
        <div style="display: flex; flex:1; align-items:center; gap: 7rem; background: #1D1C24;padding: 4px; border-radius: 4px; height:350px; justify-content:center;">
                <canvas id="barChart"></canvas>
        </div>
        <div style="display: flex; flex:1; align-items:center; gap: 7rem; background: #1D1C24;padding: 4px; border-radius: 4px; height:350px">
            <canvas id="lineChart" ></canvas>
          </div>
    </div>
</div>


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
    var chartData = {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
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
        var data = {
            labels: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
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
