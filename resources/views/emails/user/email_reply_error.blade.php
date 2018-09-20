@extends('emails/user/layouts/system')

@section('content')
	<div style="color:#2a3b47;font:500 20px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif">{{ __("Your email update couldn't be processed") }}</div>
	<p style="border-left: 3px solid #e52f28; padding: 0 0 0 10px; color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;">
		{{ __("If you are trying to update a conversation, remember you must respond from the same email address that's on your account. To send your update, please try again and send from your account email address (the email you login with).")  }}
	</p>
@endsection