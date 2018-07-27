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
    @include("mailboxes/settings_menu")
</ul>
<a href="{{ route('mailboxes.view', ['id' => $mailbox->id]) }}" class="btn btn-default btn-sidebar">{{ __("Open Mailbox") }}</a>