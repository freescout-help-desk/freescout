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
                    if ($folder_item->type == App\Folder::TYPE_SPAM) {
                        $active_count = $folder_item->total_count;
                    } else {
                        $active_count = $folder_item->getCount($folders);
                    }
                @endphp
                @if ($active_count)<span class="active-count pull-right" data-toggle="tooltip" title="{{ __("Active Conversations") }}">
                    {{ $active_count }}</span>
                @endif
            </a>
        </li>
    @endif
@endforeach