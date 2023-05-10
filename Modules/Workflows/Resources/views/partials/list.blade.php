<div class="panel-group accordion wf-index">
	@foreach ($workflows as $workflow)
        <div class="panel panel-default panel-sortable @if (!$workflow->active) panel-inactive @endif" data-wf-id="{{ $workflow->id }}">
            <div class="panel-heading">
            	<span class="handle"><i class="glyphicon glyphicon-menu-hamburger"></i></span>
                <h4 class="panel-title">
                    <a href="{{ $workflow->url() }}">
                    	@if (!$workflow->isComplete())<span class="label label-warning">{{ __('Incomplete') }}</span> {{--<i class="text-warning glyphicon glyphicon-exclamation-sign" data-toggle="tooltip" title="{{ __('Incomplete') }}"></i>--}} @elseif (!$workflow->active)<span class="label label-lightgrey">{{ __('Inactive') }}</span> @endif
                    	<span>{{ $workflow->name }}</span>
                    </a>
                </h4>
            </div>
        </div>
    @endforeach
</div>