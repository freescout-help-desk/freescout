<div class="dropdown sidebar-title">
    @php
        $auth_user = auth()->user();
        $menu_mailboxes = $auth_user->mailboxesCanView();
    @endphp
    @action('mailbox.update.before_mailbox_name', $mailbox)
    <span class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
        @if ($mailbox->isArchived())<i class="glyphicon glyphicon-lock smaller"></i> @elseif (!$mailbox->isConnected())<i class="glyphicon glyphicon-flash smaller"></i> @endif{{ $mailbox->name }} @if (count($menu_mailboxes))<span class="caret"></span>@endif
    </span>
    @if (count($menu_mailboxes))
        <ul class="dropdown-menu dm-scrollable">
            @foreach ($menu_mailboxes as $mailbox_item)
                <li @if ($mailbox_item->id == $mailbox->id)class="active"@endif><a href="{{ route(Eventy::filter('mailboxes.menu_current_route', Route::currentRouteName()), ['id'=>$mailbox_item->id]) }}">@action('mailbox.update.dropdown.before_mailbox_name', $mailbox_item)@if ($mailbox_item->isArchived())<small class="glyphicon glyphicon-lock text-help"></small> @elseif (!$mailbox_item->isConnected())<small class="glyphicon glyphicon-flash text-help"></small> @endif{{ $mailbox_item->name }}</a></li>
            @endforeach
        </ul>
    @endif
    <span class="sidebar-title-email">{{ $mailbox->email }}</span>
</div>
<ul class="sidebar-menu">
    @include("mailboxes/settings_menu")
</ul>
<a href="{{ route('mailboxes.view', ['id' => $mailbox->id]) }}" class="btn btn-bordered btn-sidebar btn-rounded" data-toggle="tooltip" title="{{ __("Open Mailbox") }}"><i class="glyphicon glyphicon-arrow-right"></i></a>
