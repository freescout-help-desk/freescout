@extends('layouts.app')

@section('title_full', __('Workflows').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading">
        {{ __('Workflows') }}<a href="{{ route('mailboxes.workflows.create', ['id' => $mailbox->id]) }}" class="btn btn-primary margin-left new-saved-reply">{{ __('New Workflow') }}</a><a href="https://freescout.net/module/workflows/" target="_blank" class="small link-help pull-right"><i class="glyphicon glyphicon-question-sign"></i> &nbsp;{{ __('Workflows Help') }}</a>
    </div>
    @if (count($automatic) || count($manual))
	    <div class="row-container">
	    	<div class="col-md-11">
			    @if (count($automatic))
			    	<h3>{{ __('Automatic') }}</h3>
			    	@include('workflows::partials/list', ['workflows' => $automatic])
			    @endif
			    @if (count($manual))
			    	<h3>{{ __('Manual') }}</h3>
			    	@include('workflows::partials/list', ['workflows' => $manual])
			    @endif
			</div>
		</div>
	@else
		@include('partials/empty', ['icon' => 'random', 'empty_header' => __("Automate actions!")])
	@endif
@endsection

@section('javascript')
    @parent
    initWorkflows();
@endsection