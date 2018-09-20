<div class="dropdown sidebar-title sidebar-title-extra">
    <span class="sidebar-title-extra-value active-count">{{ $folder->getTypeName() }} ({{ $folder->active_count }})</span>
    <span class="sidebar-title-real">{{ $mailbox->name }}</span>
</div>
<ul class="sidebar-menu">
    @foreach ($folders as $folder_item)
        @if (
            $folder->type == $folder_item->type || (
                ($folder_item->type != App\Folder::TYPE_DELETED || ($folder_item->type == App\Folder::TYPE_DELETED && $folder_item->total_count && $folder->type == App\Folder::TYPE_CLOSED)) && 
                ($folder_item->type != App\Folder::TYPE_DRAFTS || ($folder_item->type == App\Folder::TYPE_DRAFTS && $folder_item->total_count))
            )
        )
            <li class="@if ($folder_item->id == $folder->id) active @endif">
                <a href="{{ route('mailboxes.view.folder', ['id'=>$mailbox->id, 'folder_id'=>$folder_item->id]) }}" @if (!$folder_item->active_count) class="no-active" @endif><i class="glyphicon glyphicon-{{ $folder_item->getTypeIcon() }}"></i> {{ $folder_item->getTypeName() }}
                    @php
                        $active_count = $folder_item->getActiveCount($folders);
                    @endphp
                    @if ($active_count)<span class="active-count pull-right" data-toggle="tooltip" title="{{ __("Active Conversations") }}">
                        {{ $active_count }}</span>
                    @endif
                </a>
            </li>
        @endif
    @endforeach
</ul>
<div class="sidebar-buttons btn-group btn-group-justified">
    @if (Auth::user()->can('update', $mailbox))
        <div class="btn-group dropdown" data-toggle="tooltip" title="{{ __("Mailbox Settings") }}">
            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><i class="glyphicon glyphicon-cog"></i> <b class="caret"></b></a>
            <ul class="dropdown-menu" role="menu">
                @include("mailboxes/settings_menu")
            </ul>
        </div>
    @endif
    <a class="btn" href="{{ route('conversations.create', ['mailbox_id' => $mailbox->id]) }}" aria-label="{{ __("New Conversation") }}" data-toggle="tooltip" title="{{ __("New Conversation") }}"><i class="glyphicon glyphicon-envelope"></i></a>
</div>