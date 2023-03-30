@extends('emails/user/layouts/system')

@section('content')
	<p style="color:#72808e;font:400 20px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
	  {{ __("Authentication to :portal_name", ['portal_name' => $portal_name]) }}
	</p>
	<p>
		<a href="{{ $auth_link }}" style="background:{{ config('app.colors')['main_light'] }};border-color:{{ config('app.colors')['main_light'] }};border-radius:3px;border-style:solid;border-width:8px 18px;color:#ffffff;display:inline-block;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;text-decoration:none" target="_blank">{{ __('Log In') }}</a>
	</p>
	
	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		{{ __("If the 'Log In' button does not work, open the following URL in your browser:") }}<br/>
		<small>{{ $auth_link }}</small>
	</p>
@endsection

@section('footer')
	&copy; {{ date('Y') }} {{ $portal_name }}
@endsection