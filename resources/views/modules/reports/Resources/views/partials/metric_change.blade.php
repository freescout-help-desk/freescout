@if ($change == 0)
	<span class="@if (empty($no_class)) rpt-metric-change @endif text-help">
		{{ $change }}%
	</span>
@elseif ($change > 0)
	<span class="@if (empty($no_class)) rpt-metric-change @endif text-success">
		+{{ $change }}%
	</span>
@else
	<span class="@if (empty($no_class)) rpt-metric-change @endif text-danger">
		{{ $change }}%
	</span>
@endif