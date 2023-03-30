@extends('layouts.app')

@section('title', __('Customer Fields'))

@section('content')
	<div class="section-heading">
        {{ __('Customer Fields') }}<a href="{{ route('crm.ajax_html', ['action' => 'create_customer_field']) }}" class="btn btn-primary margin-left new-custom-field" data-trigger="modal" data-modal-title="{{ __('New Customer Field') }}" data-modal-no-footer="true" data-modal-size="lg" data-modal-on-show="crmInitNewCustomerField">{{ __('New Customer Field') }}</a>
    </div>

    <div class="row-container">
    	<div class="col-xs-12">
		    <form class="form-horizontal margin-top" method="POST" action="">
		    	{{ csrf_field() }}

		    	<input type="hidden" name="settings[crm.dummy]" value="dummy"/>

			    <div class="form-group">
			        <div class="col-xs-12">
			        	<strong>{{ __('Standard customer fields to show in conversation list') }}</strong>
			        	<div class="controls">
			                <label for="conv_fields_email" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="email" id="conv_fields_email" @if (in_array('email', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('Email') }} <span class="text-help">({{ __('first') }})</span></label>

			                <label for="conv_fields_phone" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="phone" id="conv_fields_phone" @if (in_array('phone', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('Phone') }} <span class="text-help">({{ __('first') }})</span></label>

			                <label for="conv_fields_company" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="company" id="conv_fields_company" @if (in_array('company', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('Company') }}</label>

			                <label for="conv_fields_job_title" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="job_title" id="conv_fields_job_title" @if (in_array('job_title', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('Job Title') }}</label>

			                <label for="conv_fields_website" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="website" id="conv_fields_website" @if (in_array('website', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('Website') }} <span class="text-help">({{ __('first') }})</span></label>

			                <label for="conv_fields_country" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="country" id="conv_fields_country" @if (in_array('country', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('Country') }}</label>

			                <label for="conv_fields_state" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="state" id="conv_fields_state" @if (in_array('state', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('State') }}</label>

			                <label for="conv_fields_city" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="city" id="conv_fields_city" @if (in_array('city', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('City') }}</label>

			                <label for="conv_fields_address" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="address" id="conv_fields_address" @if (in_array('address', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('Address') }}</label>

			                <label for="conv_fields_zip" class="checkbox inline plain"><input type="checkbox" name="settings[crm.conv_fields][]" value="zip" id="conv_fields_zip" @if (in_array('zip', $settings['crm.conv_fields'])) checked="checked" @endif> {{ __('ZIP') }}</label>
			            </div>
			            <button type="submit" class="btn btn-primary margin-top-10">{{ __('Save') }}</button>
			        
			    	</div>
			    </div>
		    </form>
	    </div>
	</div>

    @if (count($settings['customer_fields']))
	    <div class="row-container">
	    	<div class="col-md-11">
				<div class="panel-group accordion margin-top" id="cmr-customer-fields-index">
					@foreach ($settings['customer_fields'] as $customer_field)
				        <div class="panel panel-default panel-sortable" id="crm-customer-field-{{ $customer_field->id }}" data-customer-field-id="{{ $customer_field->id }}">
				            <div class="panel-heading">
				            	<span class="handle"><i class="glyphicon glyphicon-menu-hamburger"></i></span>
				                <h4 class="panel-title">
				                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse-{{ $customer_field->id }}">
				                    	<span><small class="glyphicon @if ($customer_field->display) glyphicon-eye-open @else glyphicon-eye-close @endif" title="{{ __('Display in Profile') }}"></small> {{ $customer_field->name }} <small>(ID: {{ $customer_field->id }})</small>@if ($customer_field->required) <i class="required-asterisk"></i>@endif</span>
				                    </a>
				                </h4>
				            </div>
				            <div id="collapse-{{ $customer_field->id }}" class="panel-collapse collapse">
				                <div class="panel-body">
									<form class="form-horizontal crm-customer-field-form" method="POST" action="" data-customer_field_id="{{ $customer_field->id }}" >

										@include('crm::partials/customer_fields_form_update', ['mode' => 'update'])

										<div class="form-group margin-top margin-bottom-10">
									        <div class="col-sm-10 col-sm-offset-2">
									            <button class="btn btn-primary" data-loading-text="{{ __('Saving') }}…">{{ __('Save Field') }}</button> 
									            <a href="#" class="btn btn-link text-danger crm-customer-field-delete" data-loading-text="{{ __('Deleting') }}…" data-customer_field_id="{{ $customer_field->id }}">{{ __('Delete') }}</a>
									        </div>
									    </div>
									</form>
				                </div>
				            </div>
				        </div>
				    @endforeach
			    </div>
			</div>
		</div>
	@else
		@include('partials/empty', ['icon' => 'list-alt', 'empty_header' => __("Customer Fields")])
	@endif
@endsection

@section('javascript')
    @parent
    crmInitCustomerFieldsAdmin();
@endsection