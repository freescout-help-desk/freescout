@php
    if (!isset($errors)) {
        $errors = collect([]);
    }
@endphp
@if (request()->get('success') && empty($conversation->id))
    <div class="alert alert-success text-center">
        <strong>{{ __('Your message has been sent!') }}</strong>
    </div>
    <div class="text-center margin-bottom">
        {{-- request()->url() does not return HTTPS protocol --}}
        <a href="{{ parse_url(request()->url(), PHP_URL_PATH) }}">{{ __('Submit another message') }}</a>
    </div>
@else
    @if (request()->get('success') && !empty($conversation->id))
        <div class="alert alert-success text-center">
            <strong>{{ __('Your message has been sent!') }}</strong>
        </div>
    @endif

    <div id="chat-threads"></div>

    <div id="chat-submit-form-bottom">
        <div class="form-group">
            <div class="form-group">
                <form class="" method="POST" action="" id="chat-ticket-form">
                    <textarea class="form-control" id="chat-message" rows="5" placeholder="{{ __('Type a message here') }}..."></textarea>
                </form>
            </div>
        </div>
        <div class="form-group">
            <span id="chatw-powered">@filter('chat.powered_by', 'Powered by <a href="https://freescout.net" target="_blank" title="Free open source helpdesk &amp; shared mailbox">FreeScout</a>')</span>
            <span class="pull-right" id="chat-toolbar">
                <i class="glyphicon glyphicon-send" id="chat-send-trigger" title="{{ __('Send') }}"></i> &nbsp;
                <i class="glyphicon glyphicon-paperclip" id="chat-attachment-trigger"></i> &nbsp;
                <span class="dropup">
                    <i class="glyphicon glyphicon-option-horizontal dropdown-toggle" data-toggle="dropdown"></i>
                    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">
                        <li><a href="javascript:chatShowInfoModal();void(0);">{{ __('Edit my info') }}</a></li>
                    </ul>
                </span>
                
            </span>
        </div>
    </div>
@endif