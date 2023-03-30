 <div class="rpt-metrics">

    <div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Total Conversations') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Number of conversations updated (received, replied to, status changed, assigned, etc), excluding spam and deleted conversations.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['total']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['total']['change']])
    	</div>
    </div>

	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('New Conversations') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Number of conversations created by customers or users, excluding spam and deleted conversations.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['new']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['new']['change']])
    	</div>
    </div>

	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Messages Received') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Number of messages (emails) received from customers.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['messages']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['messages']['change']])
    	</div>
    </div>
    
	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Customers') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Number of customers who created or updated conversations.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['customers']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['customers']['change']])
    	</div>
    </div>

	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Conversations per Day') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Average number of new or updated conversations per day.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['conv_day']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['conv_day']['change']])
    	</div>
    </div>
    
	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Busiest Day') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Day of the week with the highest number of new conversations on average.') }}"></i>
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['busy_day']['value'] }}
    	</div>
    </div>

</div>


<div id="rpt-chart-container">
	<div id="rpt-options">
		<div class="row">
			<div class="col-md-6">
	    		<select class="form-control rpt-option" id="rpt-chart-type">
	    			<option value="new_conv" @if ($chart['type'] == 'new_conv') selected @endif>{{ __('New Conversations') }}</option>
	    			<option value="messages" @if ($chart['type'] == 'messages') selected @endif>{{ __('Messages Received') }}</option>
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
		@if (count($table_customers))
			<div class="@if (count($table_tags)) col-md-6 @else col-md-8 col-md-offset-2 @endif">
				<table class="table table-striped">
					<tr>
						<th>{{ __('Most Active Customers') }}</th>
						<th>#</th>
					</tr>
					@foreach ($table_customers as $customer)
						@if (!empty($customer['customer']))
							<tr>
								<td><a href="{{ route('customers.update', ['id' => $customer['customer']->id]) }}" target="_blank">{{ $customer['customer']->getFullName(true) }}</a></td>
								<td>{{ (int)$customer['messages_count'] }}</td>
							</tr>
						@endif
					@endforeach
				</table>
			</div>
		@endif

		@if (count($table_tags))
			<div class="col-md-6">
				<table class="table table-striped">
					<tr>
						<th>{{ __('Tags') }}</th>
						<th>#</th>
					</tr>
					@foreach ($table_tags as $tag)
						@if (!empty($tag['tag']))
							<tr>
								<td><a href="{{ $tag['tag']->getUrl() }}" target="_blank">{{ $tag['tag']->name }}</a></td>
								<td>{{ (int)$tag['conv_count'] }}</td>
							</tr>
						@endif
					@endforeach
				</table>
			</div>
		@endif
	</div>
</div>