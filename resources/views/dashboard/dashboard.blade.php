@extends('layouts.app')
@section('content')
<div class="container">
  <div class="row vh-100">
    <div class="col-md-9"> <!-- 80% width for larger screens -->
        <div class="row">
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Total Tickets</p>
                        <h2 class="card-title text-danger" style="font-size: 80px;">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Unassigned Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">15</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Overdue Tickets</p>
                        <h2 class="card-title text-danger" style="font-size: 80px;">0</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Open Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">40</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Close Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">33</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Hold Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">40</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card" style="height: 320px; width: 480px;">
                    <canvas id="openGrouped" height="280" width="500"></canvas>  
                    <div style="margin-top: 10px; display: grid; justify-content: center;">
                        <div>
                            <span>Group by</span>
                            <select name="openGrouped" id="openGroupedFilter">
                                <option value="priority">Priority</option>
                            </select>
                            <span>Group by</span>
                            <select name="openstacked" id="openStacker">
                                <option value="none">--None--</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card" style="height: 320px; width: 480px;">
                    <canvas id="oldGrouped" height="280" width="500"></canvas>  
                    <div style="margin-top: 10px; display: grid; justify-content: center;">
                        <div>
                            <span>Group by</span>
                            <select name="openGrouped" id="openGroupedFilter">
                                <option value="priority">Priority</option>
                            </select>
                            <span>Group by</span>
                            <select name="openstacked" id="openStacker">
                                <option value="none">--None--</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SIDEBAR -->

    <div class="col-md-3"> <!-- 20% width for larger screens -->
        <div class="card">
            <div class="card-body text-right" style="height: fit-content;">
                <p class="card-text">Incidents Opened Today</p>
                <select name="incidentsToday" id="incidentsToday" style="width: 100%; padding: 2px;">
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <div class="card" style="height:fit-content;">
            <div class="card-body">
                <p class="card-text text-right">Incident State</p>
                <div>
                    <label>
                        <input type="checkbox" name="status[]" value="all">All
                    </label>
                </div>
                <div>
                    <label>
                        <input type="checkbox" name="status[]" value="new">New
                    </label>
                </div>
                    <label>
                        <input type="checkbox" name="status[]" value="inprogress">
                        In Progress
                    </label>
                <div>
                    <label>
                        <input type="checkbox" name="status[]" value="onhold">On Hold
                    </label>
                </div>
                <div>
                    <label>
                        <input type="checkbox" name="status[]" value="resolved">Resolved
                    </label>
                </div>
                <div>
                    <label>
                        <input type="checkbox" name="status[]" value="closed">Closed
                    </label>
                </div>
                <div>
                    <label>
                        <input type="checkbox" name="status[]" value="cancelled">Cancelled
                    </label>
                </div>
            </div>
        </div>
        <div class="card" style="height: fit-content;">
            <div class="card-body text-right">
                <p class="card-text">Incident Assignment Group</p>
                <select name="incidentGroup" id="incidentGroup" style="width: 100%; padding: 2px;">
                    <option value="all">All</option>
                </select>
                <input type="text" name="assignmentgrouptext" style="width: 100%; padding: 2px; margin-top: 10px;">
            </div>
        </div>
        <div class="card" style="height: fit-content;">
            <div class="card-body text-right">
                <p class="card-text">Incident Opened</p>
                <select name="incidentopened" id="incidentopened" style="width: 100%; padding: 2px;">
                    <option value="all">All</option>
                </select>
            </div>
        </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    var ctx = document.getElementById('openGrouped').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Critical', 'Planning', 'Moderate', 'High', 'Low'],
            datasets: [{
                label: 'Open Incidents Grouped',
                data: [12, 19, 3, 5, 2, ],
                backgroundColor: 'rgb(53,190,224)',
                borderColor: 'rgb(53,190,224)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: {
                    align: 'left'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5
                    }
                }
            },
        }
    });

    var oldGrouped = document.getElementById('oldGrouped').getContext('2d');
    var oldChart = new Chart(oldGrouped, {
        type: 'bar',
        data: {
            labels: ['Critical', 'Planning', 'Moderate', 'High', 'Low'],
            datasets: [{
                label: 'Open Incidents Older than 30 Days - Grouped',
                data: [12, 19, 3, 5, 2],
                backgroundColor: 'rgb(53,190,224)',
                borderColor: 'rgb(53,190,224)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: {
                    align: 'left'
                }
            },
            indexAxis: 'y',
            scales: {
                y: {
                    beginAtZero: true,
                    stepSize: 2
                }
            }
        }
    });
</script>

@endsection