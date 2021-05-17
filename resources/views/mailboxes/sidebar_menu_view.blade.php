<div class="dropdown sidebar-title sidebar-title-extra">
    <span class="sidebar-title-extra-value active-count">{{ $folder->getTypeName() }} ({{ $folder->active_count }})</span>
    <span class="sidebar-title-real mailbox-name">@include('mailboxes/partials/mute_icon', ['mailbox' => $mailbox]){{ $mailbox->name }}</span>
    <span class="sidebar-title-email">{{ $mailbox->email }}</span>
</div>
<ul class="sidebar-menu" id="folders">
    @include('mailboxes/partials/folders')
</ul>
@php
    $show_settings_btn = Auth::user()->can('viewMailboxMenu', Auth::user());
@endphp
@if (\Eventy::filter('mailbox.show_sidebar_buttons', true, $mailbox))
    <div class="sidebar-buttons btn-group btn-group-justified @if ($show_settings_btn) has-settings @endif">
        @if ($show_settings_btn)
            <div class="btn-group dropdown" data-toggle="tooltip" title="{{ __("Mailbox Settings") }}">
                <a class="btn btn-trans dropdown-toggle" data-toggle="dropdown" href="#"><i class="glyphicon glyphicon-cog"></i> <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                    @include("mailboxes/settings_menu", ['is_dropdown' => true])
                </ul>
            </div>
        @endif
        <a class="btn btn-trans" href="{{ route('conversations.create', ['mailbox_id' => $mailbox->id]) }}" aria-label="{{ __("New Conversation") }}" data-toggle="tooltip" title="{{ __("New Conversation") }}"><i class="glyphicon glyphicon-envelope"></i></a>
    </div>
@endif
@action('mailbox.after_sidebar_buttons')