@extends('emails/user/layouts/system')

@section('content')
	<div style="color:#2a3b47;font:500 20px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif">{{ __('Congratulations! Your application can send emails!') }}</div>
	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		{!! __h('This is a test system mail sent by :app_name. It means that mail settings are fine.', ['app_name' => '<strong>'.htmlspecialchars(\Config::get('app.name')).'</strong>']) !!}
	</p>
@endsection