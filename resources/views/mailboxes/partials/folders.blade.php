@foreach ($folders as $folder_item)
    @if (
        $folder->type == $folder_item->type || (
            ($folder_item->type != App\Folder::TYPE_DELETED || ($folder_item->type == App\Folder::TYPE_DELETED && $folder_item->total_count && $folder->type == App\Folder::TYPE_CLOSED)) && 
            ($folder_item->type != App\Folder::TYPE_DRAFTS || ($folder_item->type == App\Folder::TYPE_DRAFTS && $folder_item->total_count))
        )
    )
        <li class="@if ($folder_item->id == $folder->id) active @endif" data-folder_id="{{ $folder_item->id }}">
            <a href="{{ $folder_item->url($mailbox->id) }}" @if (!$folder_item->active_count) class="no-active" @endif><i class="glyphicon glyphicon-{{ $folder_item->getTypeIcon() }}"></i> <span class="folder-name">{{ $folder_item->getTypeName() }}</span>
                @php
                    if ($folder_item->type == App\Folder::TYPE_SPAM) {
                        $active_count = $folder_item->total_count;
                    } else {
                        $active_count = $folder_item->getCount($folders);
                    }
                @endphp
                @if ($active_count)
                    @if ($folder_item->type == App\Folder::TYPE_UNASSIGNED)
                        <strong class="active-count has-value pull-right" data-toggle="tooltip" title="{{ __("Active Conversations") }}">{{ $active_count }}</strong>
                    @else
                        @if ($folder_item->type == App\Folder::TYPE_MINE)<strong @else<span @endif class="active-count pull-right" data-toggle="tooltip" title="{{ __("Active Conversations") }}">{{ $active_count }}@if ($folder_item->type == App\Folder::TYPE_MINE)</strong>@else</span>@endif
                    @endif
                @endif
            </a>
        </li>
    @endif
@endforeach