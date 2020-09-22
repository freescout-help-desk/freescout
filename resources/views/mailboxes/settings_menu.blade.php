@if (Auth::user()->can('update', $mailbox))
    @if (Auth::user()->isAdmin() || Auth::user()->hasManageMailboxPermission($mailbox->id, 'edit'))
    	<li @if (Route::currentRouteName() == 'mailboxes.update')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-pencil"></i> {{ __('Edit Mailbox') }}</a></li>
    @endif
    @if (Auth::user()->isAdmin())
        <li @if (Route::currentRouteName() == 'mailboxes.connection' || Route::currentRouteName() == 'mailboxes.connection.incoming')class="active"@endif><a href="{{ route('mailboxes.connection', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-transfer"></i> {{ __('Connection Settings') }}</a></li>
    @endif
    @if (Auth::user()->isAdmin() || Auth::user()->hasManageMailboxPermission($mailbox->id, 'perm'))
        <li @if (Route::currentRouteName() == 'mailboxes.permissions')class="active"@endif><a href="{{ route('mailboxes.permissions', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-ok"></i> {{ __('Permissions') }}</a></li>
    @endif
    @if (Auth::user()->isAdmin() || Auth::user()->hasManageMailboxPermission($mailbox->id, 'auto'))
        <li @if (Route::currentRouteName() == 'mailboxes.auto_reply')class="active"@endif><a href="{{ route('mailboxes.auto_reply', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-share"></i> {{ __('Auto Reply') }}</a></li>
    @endif
    @if (Auth::user()->isAdmin() || Auth::user()->hasManageMailboxPermission($mailbox->id, 'sig'))
        <li @if (Route::currentRouteName() == 'mailboxes.email_signature')class="active"@endif><a href="{{ route('mailboxes.email_signature', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-bullhorn"></i> {{ __('Email Signature') }}</a></li>
    @endif
@endif
@action('mailboxes.settings.menu', $mailbox)
@if (!empty($is_dropdown) && Auth::user()->isAdmin())
	<li class="divider"></li>
	<li><a href="#" class="mailbox-mute-trigger" @if ($mailbox->mute) data-mute="0" @else data-mute="1" @endif data-mailbox-id="{{ $mailbox->id }}" data-loading-text="{{ __('Processing') }}…"><i class="glyphicon glyphicon-volume-off"></i> <span class="mute-text-1 @if ($mailbox->mute) hidden @endif">{{ __('Mute Notifications') }}</span><span class="mute-text-0 @if (!$mailbox->mute) hidden @endif">{{ __('Unmute Notifications') }}</span></a></li>
@endif
