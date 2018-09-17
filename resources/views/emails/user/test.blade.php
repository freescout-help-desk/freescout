@extends('emails/user/layouts/system')

@section('content')
	<div style="color:#2a3b47;font:500 20px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif">{{ __('Congratulations! Your mailbox can send emails!') }}</div>
	<p style="color:#72808e;font:400 16px/26px 'Helvetica Neue',Helvetica,Arial,sans-serif;padding:0">
		{!! __('This is a test mail sent by :app_name. It means that outgoing email settings of your :mailbox mailbox are fine.', ['app_name' => '<strong>'.\Config::get('app.name').'</strong>', 'mailbox' => '<a href="'.route('mailboxes.connection', ['id' => $mailbox->id]).'" style="border:0;color:#3197d6;text-decoration:none" target="_blank">'.$mailbox->name.'</a>']) !!}
	</p>
@endsection