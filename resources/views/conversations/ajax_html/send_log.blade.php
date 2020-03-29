@if (!$customers_log && !$users_log)
    <div class="alert alert-warning">{{ __("There were no send attempts yet") }}</div>
@else
    @if (!empty($customers_log))
    	<h5>{{ __("Emails to Customers") }}</h5>

    	<table class="table table-striped">
    		<tr>
    			<th>{{ __("Customer") }}</th>
    			<th>{{ __("Remarks") }}</th>
    			<th>{{ __("Date") }}</th>
    			<th>{{ __("Status") }}</th>
    		</tr>
			@foreach ($customers_log as $email => $logs)
				@foreach ($logs as $log)
		    		<tr>
		    			@if ($loop->index == 0)
		    				<td rowspan="{{ count($logs) }}">{{ $email }}</td>
		    			@endif
		    			<td>@if ($log->mail_type == App\SendLog::MAIL_TYPE_AUTO_REPLY) [{{ __('auto reply') }}] @else &nbsp; @endif</td>
		    			<td>{{ App\User::dateFormat($log->created_at, 'M j, Y H:i:s') }}</td>
		    			<td>
		    				<span class="@if ($log->isErrorStatus())text-danger @elseif ($log->isSuccessStatus()) text-success @endif">{{ $log->getStatusName() }}</span>
		    				@if ($log->status_message)
		    					<div class="text-help">{{ $log->status_message }}</div>
		    				@endif
		    			</td>
		    		</tr>
            	@endforeach
            @endforeach
    	</table>
	@endif

    @if (!empty($users_log))
    	<h5>{{ __("Notification Emails to Users") }}</h5>

    	<table class="table table-striped">
    		<tr>
    			<th>{{ __("User") }}</th>
    			<th>{{ __("Date") }}</th>
    			<th>{{ __("Status") }}</th>
    		</tr>
			@foreach ($users_log as $email => $logs)
				@foreach ($logs as $log)
		    		<tr>
		    			@if ($loop->index == 0)
		    				<td rowspan="{{ count($logs) }}">{{ $email }}</td>
		    			@endif
		    			<td>{{ App\User::dateFormat($log->created_at) }}</td>
		    			<td>
		    				<span class="@if ($log->isErrorStatus())text-danger @elseif ($log->isSuccessStatus()) text-success @endif">{{ $log->getStatusName() }}</span>
		    				@if ($log->status_message)
		    					<div class="text-help">{{ $log->status_message }}</div>
		    				@endif
		    			</td>
		    		</tr>
            	@endforeach
            @endforeach
    	</table>
	@endif
@endif