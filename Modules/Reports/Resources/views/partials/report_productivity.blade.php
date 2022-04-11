 <div class="rpt-metrics">

    <div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Customers Helped') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Number of customers who received replies from support agents.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['customers_helped']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['customers_helped']['change']])
    	</div>
    </div>

	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Replies Sent') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Number of replies sent from users to customers including new conversations.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['replies']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['replies']['change']])
    	</div>
    </div>

	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Replies per Day') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Average number of replies sent by users per day.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['replies_day']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['replies_day']['change']])
    	</div>
    </div>
    
	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Closed') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Number of closed conversations.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['closed']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['closed']['change']])
    	</div>
    </div>

</div>


<div id="rpt-chart-container">
	<div id="rpt-options">
		<div class="row">
			<div class="col-md-6">
	    		<select class="form-control rpt-option" id="rpt-chart-type">
	    			<option value="customers_helped" @if ($chart['type'] == 'customers_helped') selected @endif>{{ __('Customers Helped') }}</option>
	    			<option value="replies" @if ($chart['type'] == 'replies') selected @endif>{{ __('Replies Sent') }}</option>
	    			<option value="closed" @if ($chart['type'] == 'closed') selected @endif>{{ __('Closed') }}</option>
	    		</select>
	    	</div>
	    	<div class="col-md-6">
				<div class="btn-group btn-group-justified rpt-option" id="rpt-group-by">
					@if (in_array('d', $chart['group_bys']))
						<div class="btn-group" role="group">
							<button type="button" value="d" class="btn btn-default @if ($chart['group_by'] == 'd') active @endif">{{ __('Day') }}</button>
						</div>
					@endif
					@if (in_array('w', $chart['group_bys']))
						<div class="btn-group" role="group">
							<button type="button" value="w" class="btn btn-default @if ($chart['group_by'] == 'w') active @endif">{{ __('Week') }}</button>
						</div>
					@endif
					@if (in_array('m', $chart['group_bys']))
						<div class="btn-group" role="group">
							<button type="button" value="m" class="btn btn-default @if ($chart['group_by'] == 'm') active @endif">{{ __('Month') }}</button>
						</div>
					@endif
				</div>
			</div>
		</div>
	</div>
	<div id="rpt-chart">

	</div>
</div>

<div id="rpt-tables">
	<div class="row">
		@if (count($table_users))
			<div class="col-md-12">
				<table class="table table-striped">
					<tr>
						<th>{{ __('User') }}</th>
						<th>{{ __('Replies Sent') }}</th>
						<th>{{ __('Closed') }}</th>
						<th>{{ __('Customers Helped') }}</th>
						{{--<th>{{ __('Satisfaction Score') }}</th>--}}
					</tr>
					@foreach ($table_users as $user)
						@if (!empty($user['user']))
							<tr>
								<td><a href="{{ route('users.profile', ['id' => $user['user']->id]) }}" target="_blank">{{ $user['user']->getFullName() }}</a></td>
								<td>{{ (int)$user['messages_count'] }}</td>
								<td>{{ (int)$user['closed'] }}</td>
								<td>{{ (int)$user['customers_helped'] }}</td>
								{{--<td>{{ (int)$user['customers_helped'] }}</td>--}}
							</tr>
						@endif
					@endforeach
				</table>
			</div>
		@endif

	</div>
</div>