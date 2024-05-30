<ul id="connection-settings" class="nav nav-tabs nav-tabs-main">
	@php
		$route_name = Route::currentRouteName();
	@endphp
    <li @if ($route_name == 'mailboxes.connection')class="active"@endif><a href="{{ route('mailboxes.connection', ['id'=>$mailbox->id]) }}">@if ($route_name != 'mailboxes.connection' && !$mailbox->isOutActive())<i class="glyphicon glyphicon-exclamation-sign"></i> @endif{{ __('Sending Emails') }}</a></li>
    <li @if ($route_name == 'mailboxes.connection.incoming')class="active"@endif><a href="{{ route('mailboxes.connection.incoming', ['id'=>$mailbox->id]) }}">@if ($route_name != 'mailboxes.connection.incoming' && !$mailbox->isInActive())<i class="glyphicon glyphicon-exclamation-sign"></i> @endif {{ __('Fetching Emails') }}</a></li>
</ul>