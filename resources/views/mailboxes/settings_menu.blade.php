@if (Auth::user()->can('update', $mailbox))
	<li @if (Route::currentRouteName() == 'mailboxes.update')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-pencil"></i> {{ __('Edit Mailbox') }}</a></li>
	<li @if (Route::currentRouteName() == 'mailboxes.connection' || Route::currentRouteName() == 'mailboxes.connection.incoming')class="active"@endif><a href="{{ route('mailboxes.connection', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-transfer"></i> {{ __('Connection Settings') }}</a></li>
	<li @if (Route::currentRouteName() == 'mailboxes.permissions')class="active"@endif><a href="{{ route('mailboxes.permissions', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-ok"></i> {{ __('Permissions') }}</a></li>
	{{--<li @if (Route::currentRouteName() == 'mailboxes.fields')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-list"></i> {{ __('Custom Fields') }} (todo)</a></li>--}}
	<li @if (Route::currentRouteName() == 'mailboxes.auto_reply')class="active"@endif><a href="{{ route('mailboxes.auto_reply', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-share"></i> {{ __('Auto Reply') }}</a></li>
@endif
@action('mailboxes.settings.menu', $mailbox)
{{--<li @if (Route::currentRouteName() == 'mailboxes.workflows')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-random"></i> {{ __('Workflows') }} (todo)</a></li>
<li @if (Route::currentRouteName() == 'mailboxes.ratings')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-thumbs-up"></i> {{ __('Sat. Ratings') }} (todo)</a></li>--}}