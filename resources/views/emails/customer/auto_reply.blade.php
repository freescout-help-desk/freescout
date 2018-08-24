<html lang="en">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
</head>
<body bgcolor="#ffffff">
    <div id="{{ App\Mail\Mail::REPLY_SEPARATOR_HTML }}" class="{{ App\Mail\Mail::REPLY_SEPARATOR_HTML }}">
    	<div style="font-family:sans-serif;">
        	{!! $auto_reply_message !!}
		</div>
    </div>
    {{--<span height="0" style="font-size: 0px; height:0px; line-height: 0px; color:#ffffff;">{#FS:123-123#}</span>--}}
</body>
</html>