<ul id="connection-settings" class="nav nav-tabs nav-tabs-main">
    <li @if (Route::currentRouteName() == 'mailboxes.connection')class="active"@endif><a href="{{ route('mailboxes.connection', ['id'=>$mailbox->id]) }}">{{ __('Sending Emails') }}</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.connection.incoming')class="active"@endif><a href="{{ route('mailboxes.connection.incoming', ['id'=>$mailbox->id]) }}">{{ __('Fetching Emails') }}</a></li>
</ul>