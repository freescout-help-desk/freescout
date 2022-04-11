<div class="row-container">
	<form class="form-horizontal" method="POST" action="">

		<div class="form-group">
	        <label class="col-md-1 control-label">{{ __('Name') }}</label>

	        <div class="col-md-11">
	            <input class="form-control" name="name" maxlength="75" />
	        </div>
	    </div>

		<div class="form-group">
	        <label class="col-md-1 control-label">{{ __('Reply') }}</label>

	        <div class="col-md-11 new-saved-reply-editor">
	            <textarea class="form-control" name="text" rows="8">{{ $text }}</textarea>
	        </div>
	    </div>

	    @if ($categories)
			<div class="form-group">
		        <label class="col-md-1 control-label">{{ __('Category') }}</label>

		        <div class="col-md-11">
		            <select class="form-control" name="parent_saved_reply_id">
		            	<option value=""></option>
		            	@foreach($categories as $category)
		            		@if (!$category->id)
		            			<option disabled >——————————</option>
		            		@else
		            			<option value="{{ $category->id }}">{{ $category->name }}</option>
		            		@endif
		            	@endforeach
		            </select>
		        </div>
		    </div>
		@endif

		<div class="form-group margin-top margin-bottom-10">
	        <div class="col-md-11 col-md-offset-1">
	            <button type="button" class="btn btn-primary new-saved-reply-save" data-loading-text="{{ __('Saving') }}…">{{ __('Save Reply') }}</button>
	        </div>
	    </div>
	</form>
</div>