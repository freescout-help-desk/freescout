@section('stylesheets')
	@parent
	@if (!\Helper::$datepicker_included)
    	<link href="{{ asset('js/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
    @endif
@endsection

@section('javascripts')
    @parent
    @if (!\Helper::$datepicker_included)
	    {!! Minify::javascript(['/js/flatpickr/flatpickr.min.js', '/js/flatpickr/l10n/'.strtolower(Config::get('app.locale')).'.js']) !!}
	    {{-- Default config should be defined here because if "javascript" section is used when new datepicker is loaded it redefined the default config. --}}
	    @if (\Helper::isTimeFormat24())
	    	@php $flatpickr_locale = preg_replace("#\-.*#", '', Config::get('app.locale')); @endphp
		    <script type="text/javascript" {!! \Helper::cspNonceAttr() !!}>
		    	flatpickr.defaultConfig.time_24hr = true;
		    	if (typeof(flatpickr.l10ns['{{ $flatpickr_locale }}']) != "undefined") {
		    		flatpickr.localize(flatpickr.l10ns['{{ $flatpickr_locale }}']);
		    	}
		    </script>
		@endif
	@endif
@endsection

@php
	\Helper::$datepicker_included = true;
@endphp