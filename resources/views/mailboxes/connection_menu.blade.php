<ul id="connection-settings" class="nav nav-tabs">
    <li @if (Route::currentRouteName() == 'mailboxes.connection')class="active"@endif><a href="{{ route('mailboxes.connection', ['id'=>$mailbox->id]) }}">{{ __('Sending Emails') }}</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.connection.incoming')class="active"@endif><a href="{{ route('mailboxes.connection.incoming', ['id'=>$mailbox->id]) }}">{{ __('Receiving Emails') }}</a></li>
</ul>