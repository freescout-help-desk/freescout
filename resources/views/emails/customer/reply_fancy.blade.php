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
	            <table id="fsReplyAbove" class="fsReplyAbove" width="100%" border="0" cellspacing="0" cellpadding="0">
	            	@foreach ($threads as $thread)
		            	<tr>
						    <td>
						        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:0;">
						            <tr>
						                <td style="padding:8px 0 10px 0;">
						                    <h3 style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#727272; font-size:16px; line-height:22px; margin:0; font-weight:normal;">
						                    	<strong style="color:#000000;">{{ $thread->getCreatedBy()->first_name }}</strong> @if ($loop->last){{ __('sent a message') }}@else {{ __('replied') }}@endif
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

					                                {{-- todo: Satisfaction ratings --}}
					                                {{--<br><br>How would you rate my reply?<br><a href="" style="color:#50bc1c;">Great</a> &nbsp;&nbsp; <a href="" style="color:#555555;">Okay</a> &nbsp;&nbsp; <a href="" style="color:#f10000;">Not Good</a>
					                                --}}

					                                {!! $conversation->mailbox->signature !!}
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
	                <tr>
	                    <td height="0" style="font-size: 0px; line-height: 0px; color:#ffffff;">
	                    	{{-- todo: view tracking --}}
	                        {{--<img src="" alt="" />
	                        <div style="font-size: 0px; line-height: 0px; color:#ffffff !important; display:none;">{#FS:123-123#}</div>
	                    	--}}
	                    </td>
	                </tr>
	            </table>
	        </td>
	    </tr>
	</table>
</body>
</html>