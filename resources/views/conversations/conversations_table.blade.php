@if (count($conversations))
    @php
        if (empty($folder)) {
            // Create dummy folder
            $folder = new App\Folder();
            $folder->type = App\Folder::TYPE_ASSIGNED;
        }

        // Preload users and customers
        App\Conversation::loadUsers($conversations);
        App\Conversation::loadCustomers($conversations);

        // Get information on viewers
        if (empty($no_checkboxes)) {
            $viewers = App\Conversation::getViewersInfo($conversations, ['id', 'first_name', 'last_name'], [Auth::user()->id]);
        }

        $conversations = \Eventy::filter('conversations_table.preload_table_data', $conversations);
    @endphp

    @include('/conversations/partials/bulk_actions')

    <table class="table-conversations table" @if (!empty($conversations_filter)) @foreach ($conversations_filter as $filter_field => $filter_value) data-filter_{{ $filter_field }}="{{ $filter_value }}" @endforeach @endif >
        <colgroup>
            {{-- todo: without this columns table becomes not 100% wide --}}
            @if (empty($no_checkboxes))<col class="conv-current">@endif
            @if (empty($no_checkboxes))<col class="conv-cb">@endif
            @if (empty($no_customer))<col class="conv-customer">@endif
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
            @if (empty($no_checkboxes))<th class="conv-current">&nbsp;</th>@endif
            @if (empty($no_checkboxes))<th class="conv-cb"><input type="checkbox" class="toggle-all magic-checkbox" id="toggle-all"><label for="toggle-all"></label></th>@endif
            @if (empty($no_customer))
                <th class="conv-customer">
                    <span>{{ __("Customer") }}</span>
                </th>
            @endif
            <th class="conv-attachment">&nbsp;</th>
            <th class="conv-subject" colspan="2">
                <span>{{ __("Conversation") }}</span>
            </th>
            @if ($folder->type == App\Folder::TYPE_ASSIGNED || $folder->type == App\Folder::TYPE_CLOSED)
                <th class="conv-owner">
                    <span>{{ __("Assigned To") }}</span>
                </th>
                {{--<th class="conv-owner dropdown">
                    <span {{--data-target="#"- -}} class="dropdown-toggle" data-toggle="dropdown">{{ __("Assigned To") }}</span>
                    <ul class="dropdown-menu">
                          <li><a class="filter-owner" data-id="1" href="#"><span class="option-title">{{ __("Anyone") }}</span></a></li>
                          <li><a class="filter-owner" data-id="123" href="#"><span class="option-title">{{ __("Me") }}</span></a></li>
                          <li><a class="filter-owner" data-id="123" href="#"><span class="option-title">{{ __("User") }}</span></a></li>
                    </ul>
                </th>--}}
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
                <tr class="conv-row @if ($conversation->isActive()) conv-active @endif" data-conversation_id="{{ $conversation->id }}">
                    @if (empty($no_checkboxes))<td class="conv-current">@if (!empty($viewers[$conversation->id]))
                                <div class="viewer-badge @if (!empty($viewers[$conversation->id]['replying'])) viewer-replying @endif" data-toggle="tooltip" title="@if (!empty($viewers[$conversation->id]['replying'])){{ __(':user is replying', ['user' => $viewers[$conversation->id]['user']->getFullName()]) }}@else{{ __(':user is viewing', ['user' => $viewers[$conversation->id]['user']->getFullName()]) }}@endif"><div>
                            @endif</td>@endif
                    @if (empty($no_checkboxes))
                        <td class="conv-cb">
                            <input type="checkbox" class="conv-checkbox magic-checkbox" id="cb-{{ $conversation->id }}" name="cb_{{ $conversation->id }}" value="{{ $conversation->id }}"><label for="cb-{{ $conversation->id }}"></label>
                        </td>
                    @endif
                    @if (empty($no_customer))
                        <td class="conv-customer">
                            <a href="{{ $conversation->url() }}">
                                @if ($conversation->customer_id){{ $conversation->customer->getFullName(true)}}@endif&nbsp;@if ($conversation->threads_count > 1)<span class="conv-counter">{{ $conversation->threads_count }}</span>@endif
                                @if ($conversation->user_id)
                                    <small class="conv-owner-mobile text-help">
                                        {{ $conversation->user->getFullName() }} <small class="glyphicon glyphicon-user"></small>
                                    </small>
                                @endif
                            </a>
                        </td>
                    @else
                        {{-- Displayed in customer conversation history --}}
                        <td class="conv-customer conv-owner-mobile">
                            <a href="{{ $conversation->url() }}" class="help-link">
                                <small class="glyphicon glyphicon-envelope"></small> 
                                @if ($conversation->user_id)
                                     <small>&nbsp;<i class="glyphicon glyphicon-user"></i> {{ $conversation->user->getFullName() }}</small> 
                                @endif
                            </a>
                        </td>
                    @endif
                    <td class="conv-attachment">
                        <i class="glyphicon conv-star @if ($conversation->isStarredByUser()) glyphicon-star @else glyphicon-star-empty @endif" title="@if ($conversation->isStarredByUser()){{ __("Unstar Conversation") }}@else{{ __("Star Conversation") }}@endif"></i>
                        
                        @if ($conversation->has_attachments)
                            <i class="glyphicon glyphicon-paperclip"></i>
                        @else
                            &nbsp;
                        @endif
                    </td>
                    <td class="conv-subject">
                        <a href="{{ $conversation->url() }}" title="{{ __('View conversation') }}">
                            <span class="conv-fader"></span>
                            <p>
                                @if ($conversation->has_attachments)
                                    <i class="conv-attachment-mobile glyphicon glyphicon-paperclip"></i>
                                @endif
                                @if ($conversation->isPhone())
                                    <i class="glyphicon glyphicon-earphone"></i>
                                @endif
                                {{--<span class="conv-subject-number">#{{ $conversation->number }} </span>--}}@action('conversations_table.before_subject', $conversation){{ $conversation->getSubject() }}@action('conversations_table.after_subject', $conversation)
                            </p>
                            <p class="conv-preview">@if ($conversation->preview){{ $conversation->preview }}@else&nbsp;@endif</p>
                        </a>
                    </td>
                    <td class="conv-thread-count">
                        <i class="glyphicon conv-star @if ($conversation->isStarredByUser()) glyphicon-star @else glyphicon-star-empty @endif" title="@if ($conversation->isStarredByUser()){{ __("Unstar Conversation") }}@else{{ __("Star Conversation") }}@endif"></i>

                        {{--<a href="{{ $conversation->url() }}" title="{{ __('View conversation') }}">@if ($conversation->threads_count <= 1)&nbsp;@else<span>{{ $conversation->threads_count }}</span>@endif</a>--}}
                    </td>
                    @if ($folder->type == App\Folder::TYPE_ASSIGNED || $folder->type == App\Folder::TYPE_CLOSED)
                        <td class="conv-owner">
                            @if ($conversation->user_id)<a href="{{ $conversation->url() }}" title="{{ __('View conversation') }}"> {{ $conversation->user->getFullName() }} </a>@else &nbsp;@endif
                        </td>
                    @endif
                    <td class="conv-number">
                        <a href="{{ $conversation->url() }}" title="{{ __('View conversation') }}"><i>#</i>{{ $conversation->number }}</a>
                    </td>
                    <td class="conv-date">
                        <a href="{{ $conversation->url() }}" @if (!in_array($folder->type, [App\Folder::TYPE_CLOSED, App\Folder::TYPE_DRAFTS, App\Folder::TYPE_DELETED])) data-toggle="tooltip" data-html="true" data-placement="left" title="{{ $conversation->getDateTitle() }}"@else title="{{ __('View conversation') }}" @endif >{{ $conversation->getWaitingSince($folder) }}</a>
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
                    @if ($conversations->total())
                        <strong>{{ $conversations->total() }}</strong> {{ __('total conversations') }}&nbsp;|&nbsp; 
                    @endif
                    @if (isset($folder->active_count) && !$folder->isIndirect())
                        <strong>{{ $folder->getActiveCount() }}</strong> {{ __('active') }}&nbsp;|&nbsp; 
                    @endif
                    @if ($conversations)
                        {{ __('Viewing') }} <strong>{{ $conversations->firstItem() }}</strong>-<strong>{{ $conversations->lastItem() }}</strong>
                    @endif
                </td>
                <td colspan="3" class="conv-nav">
                    <div class="table-pager">
                        @if ($conversations)
                            {{ $conversations->links('conversations/conversations_pagination') }}
                        @endif
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
@else
    @include('partials/empty', ['empty_text' => __('There are no conversations here')])
@endif

@section('javascript')
    @parent
    conversationsTableInit();
@endsection