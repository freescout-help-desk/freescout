@if (Auth::user()->can('update', $mailbox))
    @if (Auth::user()->isAdmin() || Auth::user()->hasManageMailboxPermission($mailbox->id, App\Mailbox::ACCESS_PERM_EDIT) || Auth::user()->hasManageMailboxPermission($mailbox->id, App\Mailbox::ACCESS_PERM_SIGNATURE))
    	<li @if (Route::currentRouteName() == 'mailboxes.update')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-pencil"></i> {{ __('Edit Mailbox') }}</a></li>
    @endif
    @if (Auth::user()->isAdmin())
        <li @if (Route::currentRouteName() == 'mailboxes.connection' || Route::currentRouteName() == 'mailboxes.connection.incoming')class="active"@endif><a href="{{ route('mailboxes.connection', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-transfer"></i> {{ __('Connection Settings') }}</a></li>
    @endif
    @if (Auth::user()->isAdmin() || Auth::user()->hasManageMailboxPermission($mailbox->id, App\Mailbox::ACCESS_PERM_PERMISSIONS))
        <li @if (Route::currentRouteName() == 'mailboxes.permissions')class="active"@endif><a href="{{ route('mailboxes.permissions', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-ok"></i> {{ __('Permissions') }}</a></li>
    @endif
    @if (Auth::user()->isAdmin() || Auth::user()->hasManageMailboxPermission($mailbox->id, App\Mailbox::ACCESS_PERM_AUTO_REPLIES))
        <li @if (Route::currentRouteName() == 'mailboxes.auto_reply')class="active"@endif><a href="{{ route('mailboxes.auto_reply', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-share"></i> {{ __('Auto Reply') }}</a></li>
    @endif
@endif
@action('mailboxes.settings.menu', $mailbox)
@if (!empty($is_dropdown))
    <li class="divider"></li>
    <li><a href="{{ route('conversations.ajax_html', ['action' => 'default_redirect']) }}?mailbox_id={{ $mailbox->id }}" data-trigger="modal" data-modal-title="{{ __("Default Redirect") }}" data-modal-no-footer="true" data-modal-on-show="initDefaultRedirect" role="button"><i class="glyphicon glyphicon-share-alt"></i> {{ __('Default Redirect') }}…</span></a></li>
@endif
@if (!empty($is_dropdown))
	<li class="divider"></li>
	<li><a href="#" class="mailbox-mute-trigger" @if ($mailbox->mute) data-mute="0" @else data-mute="1" @endif data-mailbox-id="{{ $mailbox->id }}" data-loading-text="{{ __('Processing') }}…"><i class="glyphicon glyphicon-volume-off"></i> <span class="mute-text-1 @if ($mailbox->mute) hidden @endif">{{ __('Mute Notifications') }}</span><span class="mute-text-0 @if (!$mailbox->mute) hidden @endif">{{ __('Unmute Notifications') }}</span></a></li>
@endif
