 <div class="rpt-metrics">

    <div class="rpt-metric">
    	<div class="rpt-metric-title text-center">
    		{{ __('Total Hours Spent') }}
    	</div>
		<div class="rpt-metric-value text-center">
    		{{ $metrics['total_hours']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['total_hours']['change']])
    	</div>
    </div>

    <div class="rpt-metric">
    	<div class="rpt-metric-title text-center">
    		{{ __('Avg. Hours Spent per Update') }}
    	</div>
		<div class="rpt-metric-value text-center">
    		{{ $metrics['avg_hours']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['avg_hours']['change']])
    	</div>
    </div>

</div>


<div id="rpt-chart-container">
	<div id="rpt-chart">

	</div>
</div>

<div id="rpt-tables">
	<div class="row">
		@if (count($table_times))
			<div class="col-md-12 margin-bottom">
				<table class="table table-striped">
					<tr>
						<th>{{ __('User') }}</th>
						<th>{{ __('Time Spent') }}</th>
						<th>{{ __('Avg. Time Spent per Update') }}</th>
					</tr>
					@foreach ($table_times as $table_time)
						<tr>
							<td>@if (!empty($table_time['user']))<a href="{{ route('users.profile', ['id' => $table_time['user']->id]) }}" target="_blank">{{ $table_time['user']->getFullName() }}</a>@endif</td>
							<td>{{ $table_time['time_spent'] }}</td>
							<td>{{ $table_time['time_avg'] }}</td>
						</tr>
					@endforeach
				</table>
			</div>
		@endif

		@if (count($table_conv_times))
			<div class="col-md-6">
				<table class="table table-striped">
					<tr>
						<th>{{ __('Conversation') }}</th>
						<th>{{ __('Time Spent') }}</th>
					</tr>
					@foreach ($table_conv_times as $table_time)
						<tr>
							<td>@if (!empty($table_time['id']))<a href="{{ route('conversations.view', ['id' => $table_time['id']]) }}" target="_blank">#{{ $table_time['number'] }}</a> <small class="text-help">{{ $table_time['subject'] }}</small>@endif</td>
							<td>{{ $table_time['time_spent'] }}</td>
						</tr>
					@endforeach
				</table>
			</div>
		@endif

		@if (count($table_customer_times))
			<div class="col-md-6">
				<table class="table table-striped">
					<tr>
						<th>{{ __('Customer') }}</th>
						<th>{{ __('Time Spent') }}</th>
					</tr>
					@foreach ($table_customer_times as $table_time)
						<tr>
							<td>@if (!empty($table_time['customer']))<a href="{{ route('customers.update', ['id' => $table_time['customer']->id]) }}" target="_blank">{{ $table_time['customer']->getFullName(true) }}</a>@endif</td>
							<td>{{ $table_time['time_spent'] }}</td>
						</tr>
					@endforeach
				</table>
			</div>
		@endif

	</div>
</div>