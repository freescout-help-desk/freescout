<html lang="en">
<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
	<meta name="viewport" content="width=350px, user-scalable=yes">
	<style>
		#wrapper * { max-width: 650px !important; }
		p { margin:0 0 1.5em 0; }
		pre { font-family: Menlo, Monaco, monospace, sans-serif; padding: 0 0 1.6em 0; color:#333333; line-height:15px; }
		a { color:#3f8abf; text-decoration:none; }
	</style>
	<!--[if gte mso 12]>
		<style type="text/css">
			.tag {
				line-height: 0px !important;
				background-color: #ffffff; !important;
				font-size: 0px !important;
				padding: 0px !important;
				margin: 0px !important;
				width: 0px !important;
			}
		</style>
	<![endif]-->
</head>
<body bgcolor="#f8f9f9" style="-webkit-text-size-adjust:none; margin: 0;">
	<table bgcolor="#f8f9f9" cellspacing="0" border="0" cellpadding="0" width="100%">
		<tr>
			<td>
				<table class="content" width="100%" border="0" cellspacing="0" cellpadding="0">
				    <tr>
				        <td height="45" valign="bottom"><p align="center" style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; font-size:12px; color:#B5B9BD; line-height:16px; margin:0;">{{ $mailbox->getReplySeparator() }}</p></td>
				    </tr>
				    <tr>
				        <td height="12"></td>
				    </tr>
				    <tr>
				        <td>
				            <table align="center" width="95%" border="0" cellspacing="0" cellpadding="0" style="max-width: 650px; margin: 0 auto;">
				                <tr>
				                    <td align="center">
                                        <p style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; font-size:14px; color:#B5B9BD; line-height:16px; margin:0; margin-bottom: 6px;">
                                        	[{{ $mailbox->name }}]
                                        </p>
				                        <p style="display:inline; font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#444; line-height:22px; font-size:16px; margin:0;">
                                            {{ __('Replying to this notification will email :name', ['name' => $customer->getFirstName(true)]) }} (<a href="mailto:{{ $conversation->customer_email }}" style="color:#3f8abf; text-decoration:none;">{{ $conversation->customer_email }}</a>)
                                        </p>
				                    </td>
				                </tr>
				            </table>
				        </td>
				    </tr>
				    <tr>
				        <td height="12"></td>
				    </tr>
				</table>

				{{-- START header --}}
				<table id="wrapper" align="center" width="95%" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="border: 1px solid #d4d9dd; max-width: 650px; border-bottom: 0; margin: 0 auto;">
					<tr>
						<td bgcolor="#ffffff" style="padding:1.5em 2em; border-bottom: 1px solid #dde3e7;">
							<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff">
							    <tr>
							        <td colspan="2">
							            <p style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; line-height:15px; margin:0; font-size:12px; color:#a1a6ab; padding-bottom: 0.75em;">
							            	@if (count($threads) == 1)
							            		{{ __('Received a new conversation') }}
							            	@else
								            	@if ($thread->action_type == App\Thread::ACTION_TYPE_STATUS_CHANGED)
			                                        {!! __(":person marked as :status conversation", ['person' => '<strong>'.$thread->getCreatedBy()->getFullName(true).'</strong>', 'status' => $thread->getStatusName()]) !!}
			                                    @elseif ($thread->action_type == App\Thread::ACTION_TYPE_USER_CHANGED)
				                                    <strong>@include('emails/user/thread_by')</strong>  
													{{ __("assigned to :person conversation", ['person' => $thread->getAssigneeName(false, $user)]) }}
			                                    @elseif ($thread->type == App\Thread::TYPE_NOTE)
			                                    	{!! __(":person added a note to conversation", ['person' => '<strong>'.$thread->getCreatedBy()->getFullName(true).'</strong>']) !!}
			                                    @else
			                                    	{!! __(":person replied to conversation", ['person' => '<strong>'.$thread->getCreatedBy()->getFullName(true).'</strong>']) !!}
			                                    @endif
			                                @endif
							            	<a href="{{ \Eventy::filter('email_notification.conv_url', $conversation->url(), $user) }}" style="color:#3f8abf; text-decoration:none;">#{{ $conversation->number }}</a></p>
							        </td>
							    </tr>
							    <tr>
							        <td valign="top">
							            <table border="0" cellspacing="0" cellpadding="0">
							                <tr>
							                    <td>
							                        <h3 style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#222222; line-height:25px; font-size:20px; margin:0; font-weight:normal;">{{ $conversation->subject }}</h3>
							                    </td>
							                </tr>
							            </table>
							        </td>
							        <td align="right" valign="top">
							            <table border="0" cellspacing="0" cellpadding="5" style="margin-top: 5px;">
							                <tr>
							                    <td height="10" bgcolor="{{ $conversation->getStatusColor() }}" style="color:#ffffff; font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; line-height:12px; font-size:12px; margin-top: 3px;border-radius: 2px; {{--@if ($conversation->status == App\Conversation::STATUS_PENDING)border: 1px solid #ccc; color: #727d87;@endif--}}">{{ strtoupper($conversation->getStatusName()) }}</td>
							                </tr>
							            </table>
							            @if ($conversation->user_id)
								            <table border="0" cellspacing="0" cellpadding="0">
								                <tr>
								                    <td style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#B5B9BD; line-height:16px; font-size:12px; padding-top: 8px;text-align:right;">
								                        {{ __('Assigned to') }} {{ $conversation->user->getFullName() }}
								                    </td>
								                </tr>
								            </table>
								        @endif
							        </td>
							    </tr>
							</table>
						</td>
					</tr>
					{{-- END header --}}

	            	@foreach ($threads as $thread)

	            		@if ($thread->type == App\Thread::TYPE_LINEITEM)
	            			{{-- Line item --}}
	            			<tr>
								<td>
									<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f8f9fa" style="border-bottom:1px solid #dde3e7;">
										<tr>
											<td style="padding: 0.75em 2em;">
												<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f8f9fa">
													<tr>
														<td valign="top">
															<div style="disdivlay:inline; font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#b5b9bd; font-size:12px; line-height:16px; margin:0;">
																{!! $thread->getActionText('', true, false, $user, htmlspecialchars(view('emails/user/thread_by', ['thread' => $thread, 'user' => $user])->render())) !!}
															</div>
														</td>
														<td valign="top">
															<div style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#b5b9bd; font-size:12px; line-height:16px; margin:0;" align="right">{{ App\User::dateFormat($thread->created_at, 'M j, H:i', $user) }}</div>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
	            		@else
	            			{{-- Reply --}}
							<tr>
								<td>
									<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-bottom:1px solid #dde3e7;">
									    <tr>
									        <td style="padding: 2em;" bgcolor="@if ($thread->type == App\Thread::TYPE_MESSAGE){{ config('app.colors')['bg_user_reply'] }}@elseif ($thread->type == App\Thread::TYPE_NOTE){{ config('app.colors')['bg_note'] }}@else{{ 'ffffff' }}@endif">
									            <table width="100%" border="0" cellspacing="0" cellpadding="0">
									                <tr>
									                    <td>
									                        <h3 style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; font-size:17px; line-height:22px; margin:0 0 2px 0; font-weight:normal;">
																@if ($thread->type == App\Thread::TYPE_NOTE)
																	<span style="color:#e6b216">
																		{!! __(':person added a note', ['person' => '<strong style="color:#000000;">'.$thread->getCreatedBy()->getFullName(true).'</strong>']) !!}
																	</span>
																@else
																	@if ($thread->type == App\Thread::TYPE_MESSAGE)
																		@php
																			$action_color = config('app.colors')['text_user'];
																		@endphp
																	@else
																		@php
																			$action_color = config('app.colors')['text_customer'];
																		@endphp
																	@endif
																	<span style="color:{{ $action_color }}">
																		@if ($thread->isForwarded())
																			@php $trans_text = __(':person forwarded a conversation :forward_parent_conversation_number') @endphp
																		@elseif ($loop->last)
																			@php $trans_text = __(':person started the conversation') @endphp
																		@else
																			@php $trans_text = __(':person replied') @endphp
																		@endif
																		@php
																			$trans_params = ['person' => '<strong style="color:#000000;">'.$thread->getCreatedBy()->getFullName(true).'</strong>'];
																			if ($thread->isForwarded()) {
																				$trans_params['forward_parent_conversation_number'] = '<a href="'.route('conversations.view', ['id' => $thread->getMetaFw(App\Thread::META_FORWARD_PARENT_CONVERSATION_ID)]).'#thread-'.$thread->getMetaFw(App\Thread::META_FORWARD_PARENT_THREAD_ID).'">#'.$thread->getMetaFw(App\Thread::META_FORWARD_PARENT_CONVERSATION_NUMBER).'</a>';
																			}
																		@endphp
																		{!! __($trans_text, $trans_params) !!}
																	</span>
																@endif
															</h3>

									                    </td>
									                    <td valign="top">
									                        <div style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#B5B9BD; font-size:12px; line-height:18px; margin:0;" align="right">{{ App\User::dateFormat($thread->created_at, 'M j, H:i', $user) }}</div>
									                    </td>
									                </tr>
									                <tr>
									                    <td colspan="2" height="20">&nbsp;</td>
									                </tr>
									                <tr>
									                    <td colspan="2">
									                    	@if ($thread->isForward())
							                                    <div style="color: #b37100; background-color: #fff1cf; padding: 15px; margin-bottom: 20px; border: 1px solid #ffe19d;">
							                                        {!! __(':person forwarded this conversation. Forwarded conversation: :forward_child_conversation_number', [
							                                        'person' => ucfirst($thread->getForwardByFullName()),
							                                        'forward_child_conversation_number' => '<a href="'.route('conversations.view', ['id' => $thread->getMetaFw(App\Thread::META_FORWARD_CHILD_CONVERSATION_ID)]).'">#'.$thread->getMetaFw(App\Thread::META_FORWARD_CHILD_CONVERSATION_NUMBER).'</a>'
							                                        ]) !!}
							                                    </div>
							                                @endif
							                                @action('email_notification.before_body', $thread, $user, $conversation)
									                        <div style="font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#444; font-size:14px; line-height:20px; margin:0;">
																{!! $thread->body !!}
															</div>

															@if ($thread->has_attachments)
																<table cellspacing="0" cellpadding="6">
																	<tr>
																		<td height="15">&nbsp;</td>
																	<tr>
																	<tr>
																		<td bgcolor="#f1f3f4">
																			<p style="display:inline; margin:0; padding: 0 5px; line-height:18px; font-size:12px; font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#494848;">
																				<strong>{{ __('Attached:') }}</strong>
																				@foreach ($thread->attachments as $attachment)
																					<a href="{{ $attachment->url() }}" style="color:#3f8abf; text-decoration:none;">{{ $attachment->file_name }}</a> <span style="color:#B5B9BD;">({{ $attachment->getSizeName() }})</span>@if (!$loop->last), &nbsp;@endif
																				@endforeach
																			</p>
																		</td>
																	</tr>
																</table>
															@endif
									                    </td>
									                </tr>
									            </table>
									        </td>
									    </tr>
									</table>
								</td>
							</tr>
	            		@endif

					@endforeach
	            </table>
			</td>
		</tr>
		{{-- footer --}}
		<tr>
			<td>
				<table align="center" bgcolor="#f8f9f9" width="95%" border="0" cellspacing="0" cellpadding="0" style="max-width: 650px; margin: 0 auto;">
					<tr>
						<td height="22"></td>
					</tr>
					<tr>
						<td align="center">
							<p style="display:inline; margin:0; padding:0; font-size:12px; font-family:Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; color:#B5B9BD; line-height: 22px;" align="center"><a href="{{ \Eventy::filter('email_notification.settings_url', route('users.notifications', ['id' => $user->id]), $user) }}" style="color:#B5B9BD;">{{ __('Notification Settings') }}</a>{{ \Eventy::action('email_notification.footer_links', $mailbox, $conversation, $threads) }} - <a href="{{ \Eventy::filter('email_notification.mailbox_url', $mailbox->url(), $user) }}" style="color:#B5B9BD;">{{ $mailbox->name }}</a></p>
						</td>
					</tr>
					<tr>
						<td height="22"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td height="0" style="font-size: 0px; line-height: 0px; color:#ffffff;">	                    	
				{{-- Addition to Message-ID header to detect relies --}}
				<div style="font-size: 0px; line-height: 0px; color:#ffffff !important;">{{ \MailHelper::getMessageMarker($headers['Message-ID']) }}</div>
			</td>
		</tr>
	</table>
	<div itemscope itemtype="http://schema.org/EmailMessage">
		<div itemprop="potentialAction" itemscope itemtype="http://schema.org/ViewAction">
			<link itemprop="target" href="{{ $conversation->url() }}"/>
			<meta itemprop="name" content="{{ __('Open Conversation') }}"/>
		</div>
		<meta itemprop="description" content="{{ __('Open this conversation in :app_name', ['app_name' => 'FreeScout']) }}"/>
	</div>
</body>
</html>
