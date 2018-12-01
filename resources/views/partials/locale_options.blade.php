@php
	$dropdown_locales = config('app.locales');

	if (empty($no_custom_locales)) {
		// User may add an extra translation to the app on Translate page,
		// we should allow user to see his custom translations.
		$custom_locales = \Helper::getCustomLocales();

		if (count($custom_locales)) {
			$dropdown_locales = array_unique(array_merge($dropdown_locales, $custom_locales));
		}
	}
@endphp
@foreach ($dropdown_locales as $locale)
	@php
		$data = \Helper::getLocaleData($locale);
	@endphp
	<option value="{{ $locale }}" @if ($selected == $locale)selected="selected"@endif>{{ $data['name_en'] }}@if ($data['name'] != $data['name_en']) ({{ $data['name'] }})@endif</option>
@endforeach