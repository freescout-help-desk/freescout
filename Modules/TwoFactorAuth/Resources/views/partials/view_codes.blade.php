<p class="text-help">{{ __('Copy and keep these codes in a safe place:') }}</p>
<div class="tfa-codes">
	@foreach($codes as $code)
		@if (empty($code['used_at']))
			{{ $code['code'] }}<br/>
		@endif
	@endforeach
</div>
{{--<hr/>
<div class="margin-top">
	<p class="text-help">{{ __('Your previous recovery codes become invalid once new codes are generated.') }}</p>
	<button class="btn btn-default tfa-new-codes" data-loading-text="{{ __('Generate New Codes') }}…">{{ __('Generate New Codes') }}</button>
</div>--}}