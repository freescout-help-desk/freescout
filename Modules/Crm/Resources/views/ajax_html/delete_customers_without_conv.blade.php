<div>
	<div class="text-center">
		<div class="text-larger margin-top-10">{!! __(":count customers without conversations and messages will be deleted. Do you want to proceed?", ['count' => '<strong>'.$count.'</strong>']) !!}</div>
		<div class="form-group margin-top">
			<button class="btn btn-primary crm-delete-customers-ok" data-loading-text="{{ __("Delete") }}…">{{ __("Delete") }}</button>
			<button class="btn btn-link" data-dismiss="modal">{{ __("Cancel") }}</button>
		</div>
	</div>
</div>