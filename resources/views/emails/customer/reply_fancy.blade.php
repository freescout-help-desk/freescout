<html lang="en">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="initial-scale=1.0">
    <style>
        p { margin:0 0 1.6em 0; }
        pre { font-family: Menlo, Monaco, monospace, sans-serif; padding: 0 0 1.6em 0; color:#333333; line-height:15px; }
        img { max-width:100%; }
        a { color: #346CC4; text-decoration:none; }
    </style>
</head>
<body style="-webkit-text-size-adjust:none;">
	<table cellspacing="0" border="0" cellpadding="0" width="100%">
	    <tr>
	        <td>
	            <table id="{{ App\Misc\Mail::REPLY_SEPARATOR_HTML }}" class="{{ App\Misc\Mail::REPLY_SEPARATOR_HTML }}" width="100%" border="0" cellspacing="0" cellpadding="0">
	            	@foreach ($threads as $thread)
		            	<tr>
						    <td>
						        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:0;">
						            <tr>
						                <td style="padding:8px 0 10px 0;">
						                    <h3 style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#727272; font-size:16px; line-height:22px; margin:0; font-weight:normal;">
						                    	<strong style="color:#000000;">{{ $thread->getFromName($mailbox) }}</strong> @if ($loop->last){{ __('sent a message') }}@else {{ __('replied') }}@endif
						                	</h3>

						                    @if ($thread->getCcArray())
							                    <p style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#9F9F9F; font-size:12px; line-height:16px; margin:0;">
							                    	Cc: {{ implode(', ', $thread->getCcArray() )}}
							                    	<br>
							                    </p>
							                @endif
						                </td>
						                <td style="padding:8px 0 10px 0;" valign="top">
						                    <div style="font-family: Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#9F9F9F; font-size:12px; line-height:18px; margin:0;" align="right">{{ App\Customer::dateFormat($thread->created_at, 'M j, H:i') }}</div>
						                </td>
						            </tr>
						            <tr>
						                <td colspan="2" style="padding:8px 0 10px 0;">
						                    <div>
						                        <div style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color: #232323; font-size:13px; line-height:19px; margin:0;">
					                                {!! $thread->body !!}

					                                @action('reply_email.before_signature', $thread, $loop, $threads, $conversation, $mailbox)
					                                @if ($thread->source_via == App\Thread::PERSON_USER)
						                                <br>{!! $conversation->getSignatureProcessed(['thread' => $thread]) !!}
						                            @endif
						                            @action('reply_email.after_signature', $thread, $loop, $threads, $conversation, $mailbox)
						                            <br><br>
	                                            </div>
	                                        </div>
						                </td>
						            </tr>
						        </table>
						    </td>
						</tr>
						<tr>
						    <td height="1"><div style="border-bottom:1px solid #e7e7e7;"></div></td>
						</tr>
						<tr>
						    <td height="20"></td>
						</tr>
					@endforeach
					@if (\App\Option::get('email_branding'))
						<tr>
		                    <td height="0" style="font-size:12px; line-height:18px; font-family:Arial,'Helvetica Neue',Helvetica,Tahoma,sans-serif; color: #aaaaaa;">
							{!! __('Support powered by :app_name — Free open source help desk & shared mailbox', ['app_name' => '<a href="'.Config::get('app.freescout_url').'">'.\Config::get('app.name').'</a>']) !!}
							</td>
						</tr>
					@endif
					<tr>
						<td height="0" style="font-size: 0px; line-height: 0px; color:#ffffff;">	                    	
							@if (\App\Option::get('open_tracking'))
								<img src="{{ route('open_tracking.set_read', ['conversation_id' => $threads->first()->conversation_id, 'thread_id' => $threads->first()->id]) }}/" alt="" />
							@endif
							{{-- Addition to Message-ID header to detect relies --}}
							<div style="font-size: 0px; line-height: 0px; color:#ffffff !important; display:none;">{{ \MailHelper::getMessageMarker($headers['Message-ID']) }}</div>
						</td>
					</tr>
	            </table>
	        </td>
	    </tr>
	</table>
</body>
</html>