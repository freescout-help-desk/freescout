@extends('emails/user/layouts/system')

@section('content')
	<p style="color:#72808e;font:400 20px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
	  {!! __(':user created an account for you at :app_url', ['user' => '<strong style="color:#1e1e1e">'.$user->getFullName().'</strong>', 'app_url' => '<a href="'.\Config::get('app.url').'" style="border:0;color:#3197d6;text-decoration:none" target="_blank">'.parse_url(\Config::get('app.url'), PHP_URL_HOST).'</a>']) !!}
	</p>
	<p>
		<a href="{{ $user->urlSetup() }}" style="background:{{ config('app.colors')['main_light'] }};border-color:{{ config('app.colors')['main_light'] }};border-radius:3px;border-style:solid;border-width:8px 18px;color:#ffffff;display:inline-block;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:16px;text-decoration:none" target="_blank">{{ __('Create a Password') }}</a>
	</p>
	<div style="color:#2a3b47;font:500 20px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif">{{ __('Welcome to the team!') }}</div>
	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		{!! __('Someone on your team created an account for you.') !!}
	</p>
@endsection