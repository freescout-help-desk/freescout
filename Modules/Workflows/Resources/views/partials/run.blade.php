<div class="row-container wf-run-list">
	@foreach($workflows as $workflow)
		<button class="btn btn-primary btn-block" data-wf-id="{{ $workflow->id }}" data-loading-text="{{ $workflow->name }}…"><i class="glyphicon glyphicon-triangle-right"></i> &nbsp;{{ $workflow->name }}</button>
	@endforeach
</div>