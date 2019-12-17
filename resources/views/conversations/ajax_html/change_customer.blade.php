<div class="form-group">
	<select type="text" class="form-control input-md change-customer-input" data-customer_email="{{ $conversation->customer_email }}" placeholder="{{ __('Search for a customer by name or email') }}…" autocomplete="off"></select>
</div>

<div id="change-customer-suggestions" class="hidden">
	<h3>{{ __('Suggestions') }}</h3>
</div>

<div id="change-customer-create" class="hidden">
	<h3 class="customer-not-found-title">{{ __('No customers found. Would you like to create one?') }}</h3>
	<h3 class="customer-create-title">{{ __('Create a new customer') }}</h3>

	<form class="form-inline">
	    
    	<div class="form-group">
        	<input type="text" class="form-control" name="first_name" placeholder="{{ __('First Name') }}" required="required">
        	<span class="help-block"></span>
        </div>
    
    	<div class="form-group">
        	<input type="text" class="form-control" name="last_name" placeholder="{{ __('Last Name') }}">
        	<span class="help-block"></span>
        </div>
  
  		<div class="form-group">
        	<input type="email" class="form-control" name="email" placeholder="{{ __('Email') }}" required="required">
        	<span class="help-block"></span>
        </div>
  
    	<div class="form-group">
        	<button class="btn btn-primary" type="submit" data-loading-text="{{ __('Saving') }}…">{{ __('Save') }}</button>
        </div>
	</form>
	
</div>

<div class="margin-top" id="change-customer-create-trigger">
	<a href="#">{{ __('Create a new customer') }}</a>
</div>