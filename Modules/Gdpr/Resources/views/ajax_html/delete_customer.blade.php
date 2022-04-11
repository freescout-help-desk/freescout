<div>
	<div class="text-center">
		<div class="text-larger margin-top-10 text-danger">{!! __("Are you sure you want to delete this customer and :conv_count conversation(s) belonging to the customer? Agents' replies to the customer also will be deleted.", ['conv_count' => '<strong>'.$conv_count.'</strong>']) !!}</div>
		<div class="form-group margin-top">
			<button class="btn btn-primary gdpr-delete-customer-ok" data-loading-text="{{ __("Delete") }}…">{{ __("Delete") }}</button>
			<button class="btn btn-link" data-dismiss="modal">{{ __("Cancel") }}</button>
		</div>
	</div>
</div>