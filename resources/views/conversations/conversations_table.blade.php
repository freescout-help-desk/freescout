<table class="table-conversations table">
    <colgroup>
        <col class="conv-current">
        <col class="conv-cb">
        <col class="conv-customer">
        <col class="conv-attachment">
        <col class="conv-subject">
        <col class="conv-thread-count">
        @if ($folder->type == App\Folder::TYPE_ASSIGNED || $folder->type == App\Folder::TYPE_CLOSED)
            <col class="conv-owner">
        @endif
        <col class="conv-number">
        <col class="conv-date">
    </colgroup>
    <thead>
    <tr>
        <th class="conv-cb" colspan="2"><input type="checkbox" class="toggle-all"></th>
        <th class="conv-customer">
            <span>{{ __("Customer") }}</span>
        </th>
        <th class="conv-attachment">&nbsp;</th>
        <th class="conv-subject" colspan="2">
            <span>{{ __("Conversation") }}</span>
        </th>
        @if ($folder->type == App\Folder::TYPE_ASSIGNED || $folder->type == App\Folder::TYPE_CLOSED)
            <th class="conv-owner dropdown" data-toggle="tooltip" title="{{ __("Filter by Assigned To") }}">
                <span {{--data-target="#"--}} class="dropdown-toggle" data-toggle="dropdown">{{ __("Assigned To") }}</span>
                <ul class="dropdown-menu">
                      <li><a class="filter-owner" data-id="1" href="#"><span class="option-title">{{ __("Anyone") }}</span></a></li>
                      <li><a class="filter-owner" data-id="123" href="#"><span class="option-title">{{ __("Me") }}</span></a></li>
                      <li><a class="filter-owner" data-id="123" href="#"><span class="option-title">{{ __("User") }}</span></a></li>
                </ul>
            </th>
        @endif
        <th class="conv-number">
            <span>{{ __("Number") }}</span>
        </th>
        <th class="conv-date">
            <span>
                @if ($folder->type == App\Folder::TYPE_CLOSED)
                    {{ __("Closed") }}
                @elseif ($folder->type == App\Folder::TYPE_DRAFTS)
                    {{ __("Last Updated") }}
                @elseif ($folder->type == App\Folder::TYPE_DELETED)
                    {{ __("Deleted") }}
                @else
                    {{ __("Waiting Since") }}
                @endif
            </span>
        </th>
      </tr>
    </thead>
    <tbody>
        @foreach ($conversations as $conversation)
            <tr class="conv-row @if ($conversation->isActive()) conv-active @endif">
                <td class="conv-current"></td>
                <td class="conv-cb">
                    <input type="checkbox" id="cb-{{ $conversation->id }}" name="cb_{{ $conversation->id }}" value="{{ $conversation->id }}">
                </td>
                <td class="conv-customer">
                    <a href="{{ $conversation->url() }}">
                        {{ $conversation->customer->getFullName(true)}}
                    </a>    
                </td>
                <td class="conv-attachment">
                    @if ($conversation->has_attachments)
                        <i class="glyphicon glyphicon-paperclip"></i>
                    @else
                        &nbsp;
                    @endif
                </td>
                <td class="conv-subject">
                    <a href="{{ $conversation->url() }}" title="{{ __('View conversation') }}">
                        <span class="conv-fader"></span>
                        <p><span class="conv-subject-number">#{{ $conversation->number }} </span>{{ $conversation->subject }}</p>
                        <p class="conv-preview">{{ $conversation->preview }}</p>
                    </a>
                </td>
                <td class="conv-thread-count">
                    <a href="{{ $conversation->url() }}" title="{{ __('View conversation') }}">@if ($conversation->threads_count <= 1)&nbsp;@else<span>{{ $conversation->threads_count }}</span>@endif</a>
                </td>
                @if ($folder->type == App\Folder::TYPE_ASSIGNED || $folder->type == App\Folder::TYPE_CLOSED)
                    <td class="conv-owner">
                        @if ($conversation->user)<a href="{{ $conversation->url() }}" title="{{ __('View conversation') }}"> {{ $conversation->user->getFullName() }} </a>@else &nbsp;@endif
                    </td>
                @endif
                <td class="conv-number">
                    <a href="{{ $conversation->url() }}" title="{{ __('View conversation') }}">{{ $conversation->number }}</a>
                </td>
                <td class="conv-date">
                    <a href="{{ $conversation->url() }}" @if (!in_array($conversation->type, [App\Folder::TYPE_CLOSED, App\Folder::TYPE_DRAFTS, App\Folder::TYPE_DELETED])) data-toggle="tooltip" data-html="true" data-placement="left" title="{{ $conversation->getDateTitle() }}"@else title="{{ __('View conversation') }}" @endif >
                        @if ($folder->type == App\Folder::TYPE_CLOSED)
                            {{ App\User::dateDiffForHumans($conversation->closed_at) }}
                        @elseif ($folder->type == App\Folder::TYPE_DRAFTS)
                            {{ App\User::dateDiffForHumans($conversation->updated_at) }}
                        @elseif ($folder->type == App\Folder::TYPE_DELETED)
                            {{ App\User::dateDiffForHumans($conversation->updated_at) }}
                        @else
                            {{ App\User::dateDiffForHumans($conversation->last_reply_at) }}
                        @endif
                    </a>
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            @if ($folder->type == App\Folder::TYPE_ASSIGNED || $folder->type == App\Folder::TYPE_CLOSED)
                <td class="conv-totals" colspan="6">
            @else
                <td class="conv-totals" colspan="5">
            @endif
                @if (isset($folder->total_count))
                    <strong>{{ $folder->total_count }}</strong> {{ __('total conversations') }}&nbsp;|&nbsp; 
                @endif
                @if (isset($folder->active_count))
                    <strong>{{ $folder->active_count }}</strong> {{ __('active') }}&nbsp;|&nbsp; 
                @endif
                {{ __('Viewing') }} <strong>{{ $conversations->firstItem() }}</strong>-<strong>{{ $conversations->lastItem() }}</strong>
            </td>
            <td colspan="3" class="conv-nav">
                <div class="table-pager">
                    {{ $conversations->links('conversations/conversations_pagination') }}
                </div>
            </td>
        </tr>
    </tfoot>
</table>