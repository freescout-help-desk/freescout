<div class="dropdown sidebar-title sidebar-title-extra @if (!$mailbox->email) sidebar-no-email @endif">
    @if (isset($folder))<span class="sidebar-title-extra-value active-count">{{ $folder->getTypeName() }} ({{ $folder->active_count }})</span>@endif
    @action('mailbox.view.before_name', $mailbox)
    <span class="sidebar-title-real mailbox-name">@include('mailboxes/partials/mute_icon', ['mailbox' => $mailbox]){{ $mailbox->name }}</span>
    <span class="sidebar-title-email">{{ $mailbox->email }}</span>
</div>
@php
    $is_in_chat_mode = $is_in_chat_mode ?? (isset($conversation) && $conversation->isInChatMode());
@endphp
<ul class="sidebar-menu @if ($is_in_chat_mode) chats @endif" id="folders">
    @if ($is_in_chat_mode)
        @include('mailboxes/partials/chat_list')
    @else
        @include('mailboxes/partials/folders')
    @endif
</ul>
@if (!$is_in_chat_mode)
    @php
        $show_settings_btn = Auth::user()->can('viewMailboxMenu', Auth::user());
    @endphp
    @if (\Eventy::filter('mailbox.show_buttons', true, $mailbox))
        <div class="sidebar-buttons btn-group btn-group-justified @if ($show_settings_btn) has-settings @endif">
            @if ($show_settings_btn)
                <div class="btn-group dropdown" data-toggle="tooltip" title="{{ __("Mailbox Settings") }}">
                    <a class="btn btn-trans dropdown-toggle" data-toggle="dropdown" href="#"><i class="glyphicon glyphicon-cog"></i> <b class="caret"></b></a>
                    <ul class="dropdown-menu" role="menu">
                        @include("mailboxes/settings_menu", ['is_dropdown' => true])
                    </ul>
                </div>
            @endif
            <a class="btn btn-trans" href="{{ route('conversations.create', ['mailbox_id' => $mailbox->id]) }}" aria-label="{{ __("New Conversation") }}" data-toggle="tooltip" title="{{ __("New Conversation") }}" role="button"><i class="glyphicon glyphicon-envelope"></i></a>
        </div>
    @endif
    @action('mailbox.after_sidebar_buttons')
@endif