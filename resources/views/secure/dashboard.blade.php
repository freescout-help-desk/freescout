@extends('layouts.app')
@section('content')
<div class="container" style="padding: 0 60px;">
    <div class="row container" style="border: 2px solid #eee; padding: 10px; border-radius: 4px;">
        <div class="col-sm-2">
            <label for="ticket">Ticket Category</label>
            <select name="ticket" id="" ticket style="background-color: transparent; border-radius: 4px; margin-left: 4px;">
                <option value="none"></option>
                <option value="open">OPEN</option>
                <option value="hold">HOLD</option>
                <option value="closed">CLOSED</option>
            </select>
        </div>
        <div class="col-sm-2">
            <label for="ticket">Product</label>
            <select name="ticket" id="" ticket style="background-color: transparent; border-radius: 4px; margin-left: 4px;">
                <option value="none"></option>
                <option value="open">Product 1</option>
                <option value="hold">Product 2</option>
                <option value="closed">Product 3</option>
            </select>
        </div>
        <div class="col-sm-2">
            <label for="ticket">Type</label>
            <select name="ticket" id="" ticket style="background-color: transparent; border-radius: 4px; margin-left: 4px;">
                <option value="none"></option>
                <option value="open">Type 1</option>
                <option value="hold">Type 2</option>
                <option value="closed">Type 3</option>
            </select>
        </div>
        <div class="col-sm-2">
            <label for="ticket">Mailbox</label>
            <select name="ticket" id="" ticket style="background-color: transparent; border-radius: 4px; margin-left: 4px;">
                <option value="none"></option>
                <option value="open">Mailbox 1</option>
                <option value="hold">Mailbox 2</option>
                <option value="closed">Mailbox 3</option>
            </select>
        </div>
        <div class="col-sm-2" style="display: flex; align-items: center;">
            <label for="date">Date</label>
            <input type="date" class="form-control" id="date" style="margin-left: 10px; margin-bottom: 2px; border-radius: 4px;">
        </div>
    </div>

    <div class="row text-center" style="margin-top: 6rem;">
        <div class="col-md-4">
            <p>Total Tickets</p>
            <h1>12</h1>
        </div>
        <div class="col-md-4">
            <p>Unassigned Tickets</p>
            <h1>4</h1>
        </div>
        <div class="col-md-4">
            <p>Overdue Tickets</p>
            <h1>5</h1>
        </div>
    </div>

    <div class="row text-center" style="margin-top: 4rem;">
        <div class="col-md-4">
            <p>Open Tickets</p>
            <h1>10</h1>
        </div>
        <div class="col-md-4">
            <p>Close Tickets</p>
            <h1>2</h1>
        </div>
        <div class="col-md-4">
            <p>Hold Tickets</p>
            <h1>3</h1>
        </div>
    </div>
    <div class="row" style="margin-top: 10rem;">
        <div class="col-md-5" style="display: flex; gap: 2rem; background: #1D1C24; padding: 20px; border-radius: 4px;">
            <div>
                <canvas id="donutChart" height="241"></canvas>
            </div>
            <div>
                <div style="display: flex; align-items:flex-start; gap: 10px;">
                    <div style="background-color: plum; height: 20px; width: 20px; border-radius: 4px;"></div>
                    <div>
                        <p>Number Of Tickets</p>
                        <p>50</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6">
                        <p>Open tickets</p>
                        <p>15</p>
                    </div>
                    <div class="col-sm-6">
                        <p>Close tickets</p>
                        <p>5</p>
                    </div>
                    <div class="col-sm-6">
                        <p>Hold tickets</p>
                        <p>30</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5" style="background: #1D1C24; padding: 4px; border-radius: 4px; margin-left: 8rem;">
            <canvas id="lineChart"></canvas>
        </div>
    </div>

    <div class="row" style="margin-top: 4rem; display:flex; justify-content:center;">
        <div class="col-md-11">
            <canvas id="barChart" style="background: #1D1C24;" height="80"></canvas>
        </div>
    </div>

    <div class="row" style="margin-top: 4rem; display:flex; justify-content:center;">
        <div class="col-md-5">
            <canvas id="horizontalChart" style="background: #1D1C24;"></canvas>
        </div>
        <div class="col-md-0"></div>
        <div class="col-md-5">
            <!-- content for second column here -->
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/charts.js') }}" />

@endsection