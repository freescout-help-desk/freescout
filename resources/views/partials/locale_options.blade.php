@foreach (config('app.locales') as $locale)
	@php
		$data = \Helper::getLocaleData($locale);
	@endphp
	<option value="{{ $locale }}" @if ($selected == $locale)selected="selected"@endif>{{ $data['name_en'] }}@if ($data['name'] != $data['name_en']) ({{ $data['name'] }})@endif</option>
@endforeach