<html lang="en">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="initial-scale=1.0">
    <style>
        p { margin:0 0 1.6em 0; }
        pre { font-family: Menlo, Monaco, monospace, sans-serif; padding: 0 0 1.6em 0; color:#333333; line-height:15px; }
        img { max-width:100%; border-radius: 50%; }
        a { color: #346CC4; text-decoration:none; }
        .email-container { width: 100%!important; margin: 0; padding: 0; font-family: Arial, 'Helvetica Neue', Helvetica, Tahoma, sans-serif; }
        .header { display: flex; align-items: center; padding: 10px 0; }
        .avatar { width: 40px; height: 40px; margin-right: 10px; }
        .agent-info { color: #727272; font-size: 15px; line-height: 21px; }
        .agent-name { color: #000000; font-weight: bold; }
        .content { color: #232323; font-size: 13px; line-height: 19px; padding: 8px 0 10px 0; }
        .footer { color: #aaaaaa; font-size: 12px; line-height: 18px; margin-top: 30px; }
    </style>
</head>
<body style="-webkit-text-size-adjust:none;">
    <div class="email-container">
        @php $reply_separator = \MailHelper::getHashedReplySeparator($headers['Message-ID']); @endphp
        <div id="{{ $reply_separator }}" class="{{ $reply_separator }}" data-fs="{{ $reply_separator }}">
            @foreach ($threads as $thread)
                <div style="width:100%; margin:0;">
                    @if (!$loop->first)
                        <div class="header">
                            <img class="avatar" src="{{ $thread->user()->getPhotoUrl() }}" alt="Agent Avatar">
                            <div class="agent-info">
                                <span class="agent-name">{{ $thread->getFromName($mailbox) }}</span>
                                <div>{{ App\Customer::dateFormat($thread->created_at, 'M j, H:i') }}</div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="content">
                    @if ($thread->source_via == App\Thread::PERSON_USER && $mailbox->before_reply && $loop->first)
                        <span style="color:#b5b5b5">{{ $mailbox->before_reply }}</span><br><br>
                    @endif
                    {!! $thread->body !!}
                </div>
                @action('reply_email.before_signature', $thread, $loop, $threads, $conversation, $mailbox, $threads_count)
                @if ($thread->source_via == App\Thread::PERSON_USER && \Eventy::filter('reply_email.include_signature', true, $thread))
                    <br>{!! $conversation->getSignatureProcessed(['thread' => $thread]) !!}
                @endif
                @action('reply_email.after_signature', $thread, $loop, $threads, $conversation, $mailbox, $threads_count)
                <br><br>
                <div style="border-bottom:1px solid #e7e7e7;"></div>
                <div style="height:15px;"></div>
            @endforeach
            @if (\App\Option::get('email_branding'))
                <div class="footer">
                    {!! __('Support powered by :app_name — Free open source help desk & shared mailbox', ['app_name' => '<a href="https://landing.freescout.net">'.\Config::get('app.name').'</a>']) !!}
                </div>
            @endif
        </div>
    </div>
</body>
</html>
