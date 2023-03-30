@extends('enduserportal::layouts.widget_form')

@section('title', __('Contact us'))

@section('body_attrs')@parent data-mailbox_id_encoded="{{ request()->mailbox_id }}" data-is_widget="1"@endsection

@section('content')
	
	<div id="eupw-form-wrapper">
		@include('enduserportal::partials/submit_form', ['submit_area_append' => \Eventy::filter('enduserportal.powered_by', '<p id="eupw-powered">Powered by <a href="https://freescout.net/" target="_blank" title="Free open source helpdesk &amp; shared mailbox">FreeScout</a></p>'), 'submit_btn_attrs' => (Request::get('color') ? 'style="background-color: '.Request::get('color').'; border-color: '.Request::get('color').'"': '')])
	</div>
@endsection

@section('eup_javascript')
    @parent
    eupInitSubmit();
@endsection