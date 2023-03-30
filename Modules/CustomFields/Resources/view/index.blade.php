@extends('layouts.app')

@section('title_full', __('Custom Fields').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading">
        {{ __('Custom Fields') }}<a href="{{ route('mailboxes.custom_fields.ajax_html', ['action' => 'create']) }}" class="btn btn-primary margin-left new-custom-field" data-trigger="modal" data-modal-title="{{ __('New Custom Field') }}" data-modal-no-footer="true" data-modal-on-show="initNewCustomField">{{ __('New Custom Field') }}</a>
    </div>
    @if (count($custom_fields))
	    <div class="row-container">
	    	<div class="col-md-11">
				<div class="panel-group accordion margin-top" id="custom-fields-index">
					@foreach ($custom_fields as $custom_field)
				        <div class="panel panel-default panel-sortable" id="custom-field-{{ $custom_field->id }}" data-custom-field-id="{{ $custom_field->id }}">
				            <div class="panel-heading">
				            	<span class="handle"><i class="glyphicon glyphicon-menu-hamburger"></i></span>
				                <h4 class="panel-title">
				                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse-{{ $custom_field->id }}">
				                    	<span>{{ $custom_field->name }} @if ($custom_field->required)<i class="required-asterisk"></i>@endif</span>
				                    </a>
				                </h4>
				            </div>
				            <div id="collapse-{{ $custom_field->id }}" class="panel-collapse collapse">
				                <div class="panel-body">
									<form class="form-horizontal custom-field-form" method="POST" action="" data-custom_field_id="{{ $custom_field->id }}" >

										@include('customfields::partials/form_update', ['mode' => 'update'])

										<div class="form-group margin-top margin-bottom-10">
									        <div class="col-sm-10 col-sm-offset-2">
									            <button class="btn btn-primary" data-loading-text="{{ __('Saving') }}…">{{ __('Save Field') }}</button> 
									            <a href="#" class="btn btn-link text-danger cf-delete-trigger" data-loading-text="{{ __('Deleting') }}…" data-custom_field_id="{{ $custom_field->id }}">{{ __('Delete') }}</a>
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
		@include('partials/empty', ['icon' => 'th-list', 'empty_header' => __("Store additional data on conversations!"), 'empty_text' => __('Custom fields let you add things like text boxes, dropdown lists, date pickers, and other fields to conversations so you can store additional information.')])
	@endif
@endsection

@section('javascript')
    @parent
    initCustomFieldsAdmin();
@endsection