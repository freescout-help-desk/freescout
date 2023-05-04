@extends('emails/user/layouts/system')

@section('content')
	<div style="color:#2a3b47;font:500 20px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif">{{ __('Congratulations! Your mailbox can send emails!') }}</div>
	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		This is a test email
	</p>
@endsection