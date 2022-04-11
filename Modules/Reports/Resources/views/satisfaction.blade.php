@extends('layouts.app')

@section('title', __('Satisfaction Report'))
@section('content_class', 'content-full')

@section('content')
<div class="container">
    <div class="rpt-header">
        <form id="rpt-filters">
        	<div class="rpt-title">{{ __('Satisfaction Report') }}</div>
        	@include('reports::partials/filters')
        </form>
    </div>

    <div id="rpt-report" data-report-name="{{ \Reports::REPORT_SATISFACTION }}" data-chart-type="pie">
   		@include('partials/empty', ['icon' => 'refresh', 'extra_class' => 'glyphicon-spin'])
	</div>
</div>
@include('partials/include_datepicker')
@endsection

@section('javascript')
    @parent
    initReports();
@endsection

@section('body_bottom')
    @parent
    @include('reports::partials/scripts')
@endsection