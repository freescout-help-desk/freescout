<div class="dropdown sidebar-title">
    <span class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
        {{ $mailbox->name }} @if (isset($mailboxes) && count($mailboxes))<span class="caret"></span>@endif
    </span>
    @if (isset($mailboxes) && count($mailboxes))
        <ul class="dropdown-menu">
            @foreach ($mailboxes as $mailbox_item)
                <li @if ($mailbox_item->id == $mailbox->id)class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox_item->id]) }}">{{ $mailbox_item->name }}</a></li>
            @endforeach
        </ul>
    @endif
</div>
<ul class="sidebar-menu">
    <li @if (Route::currentRouteName() == 'mailboxes.update')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-envelope"></i> {{ __('Edit Mailbox') }}</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.connection' || Route::currentRouteName() == 'mailboxes.connection.incoming')class="active"@endif><a href="{{ route('mailboxes.connection', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-cog"></i> {{ __('Connection Settings') }}</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.permissions')class="active"@endif><a href="{{ route('mailboxes.permissions', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-ok"></i> {{ __('Permissions') }}</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.fields')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-list"></i> {{ __('Custom Fields') }} (todo)</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.autoreply')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-share"></i> {{ __('Auto Reply') }} (todo)</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.savedreplies')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-floppy-save"></i> {{ __('Saved Replies') }} (todo)</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.workflows')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-random"></i> {{ __('Workflows') }} (todo)</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.ratings')class="active"@endif><a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-thumbs-up"></i> {{ __('Sat. Ratings') }} (todo)</a></li>
   
</ul>