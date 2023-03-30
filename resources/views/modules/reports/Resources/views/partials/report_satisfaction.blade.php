 <div class="rpt-metrics">

    <div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Ratings') }}
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['ratings']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['ratings']['change']])
    	</div>
    </div>

	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Great') }}
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['great']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['great']['change']])
    	</div>
    </div>

	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Okay') }}
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['okay']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['okay']['change']])
    	</div>
    </div>
    
	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Not Good') }}
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['notgood']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['notgood']['change']])
    	</div>
    </div>

	<div class="rpt-metric">
    	<div class="rpt-metric-title">
    		{{ __('Satisfaction Score') }}
    	</div>
		<div class="rpt-metric-value">
    		{{ $metrics['satscore']['value'] }}
    		@include('reports::partials/metric_change', ['change' => $metrics['satscore']['change']])
    	</div>
    </div>

</div>


<div id="rpt-chart-container">
	<div id="rpt-chart">

	</div>
</div>

<div id="rpt-tables">
	<div class="row">
		@if (count($table_ratings))
			<div class="col-md-12">
				<table class="table table-striped">
					<tr>
						<th>#</th>
						<th>{{ __('Customer') }}</th>
						<th>{{ __('User') }}</th>
						<th>{{ __('Rating') }}</th>
						<th>{{ __('Comment') }}</th>
					</tr>
					@foreach ($table_ratings as $table_rating)
						<tr>
							<td><a href="{{ route('conversations.view', ['id' => $table_rating['id']]) }}" target="_blank">#{{ $table_rating['number'] }}</a><br/><small class="text-help">{{ App\User::dateFormat($table_rating['created_at']) }}</small></td>
							<td>@if (!empty($table_rating['customer']))<a href="{{ route('customers.update', ['id' => $table_rating['customer']->id]) }}" target="_blank">{{ $table_rating['customer']->getFullName(true) }}</a>@endif</td>
							<td>@if (!empty($table_rating['user']))<a href="{{ route('users.profile', ['id' => $table_rating['user']->id]) }}" target="_blank">{{ $table_rating['user']->getFullName() }}</a>@endif</td>
							<td>
								@if ((int)$table_rating['rating'] == \SatRatingsHelper::RATING_BAD)
									<span class="text-danger">{{ __('Not Good') }}</span>
								@elseif ((int)$table_rating['rating'] == \SatRatingsHelper::RATING_GREAT)
									<span class="text-success">{{ __('Great') }}</span>
								@elseif ((int)$table_rating['rating'] == \SatRatingsHelper::RATING_OKAY)
									{{ __('Okay') }}
								@endif
							</td>
							<td>{{ $table_rating['rating_comment'] }}</td>
						</tr>
					@endforeach
				</table>
			</div>
		@endif

	</div>
</div>