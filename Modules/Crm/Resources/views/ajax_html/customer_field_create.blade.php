<div class="row-container">
	<form class="form-horizontal crm-new-customer-field-form" method="POST" action="">

		@include('crm::partials/customer_fields_form_update', ['mode' => 'create'])

		<div class="form-group margin-top margin-bottom-10">
	        <div class="col-sm-10 col-sm-offset-2">
	            <button class="btn btn-primary" data-loading-text="{{ __('Saving') }}…">{{ __('Save Field') }}</button>
	        </div>
	    </div>
	</form>
</div>