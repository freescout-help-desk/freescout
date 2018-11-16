<html lang="en">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
</head>
<body bgcolor="#ffffff">
    <div id="{{ App\Misc\Mail::REPLY_SEPARATOR_HTML }}" class="{{ App\Misc\Mail::REPLY_SEPARATOR_HTML }}">
    	<div style="font-family:sans-serif;">
        	{!! $auto_reply_message !!}
		</div>
		@if (\App\Option::get('email_branding'))
			<div style="font-size:12px; line-height:18px; font-family:Arial,'Helvetica Neue',Helvetica,Tahoma,sans-serif; color: #aaaaaa; border-top: 1px solid #eeeeee; margin: 10px 0 14px 0; padding-top: 10px;">
				{!! __('Support powered by :app_name — Free open source help desk & shared mailbox', ['app_name' => '<a href="'.Config::get('app.freescout_url').'">'.\Config::get('app.name').'</a>']) !!}
			</div>
		@endif
    </div>
    <span height="0" style="font-size: 0px; height:0px; line-height: 0px; color:#ffffff;">{{ \MailHelper::getMessageMarker($headers['Message-ID']) }}</span>
</body>
</html>