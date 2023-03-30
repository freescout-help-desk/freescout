@if (count($conversations))
    @php
        $show_assigned = false;
    @endphp

    <table class="table-conversations table eup-table-tickets" data-page="{{ (int)request()->get('page', 1) }}">
        <colgroup>
            {{-- todo: without this columns table becomes not 100% wide --}}
            @if (empty($no_checkboxes))<col class="conv-current">@endif
            {{--@if (empty($no_checkboxes))<col class="conv-cb">@endif--}}
            <col class="conv-attachment">
            <col class="conv-subject">
            <col class="conv-thread-count">
            @if ($show_assigned)
                <col class="conv-owner">
            @endif
            <col class="conv-number">
            @if (empty($no_customer))<col class="conv-customer">@endif
            <col class="conv-date">
        </colgroup>
        <thead>
        <tr>
            @if (empty($no_checkboxes))<th class="conv-current">&nbsp;</th>@endif
            {{--@if (empty($no_checkboxes))<th class="conv-cb"><input type="checkbox" class="toggle-all magic-checkbox" id="toggle-all"><label for="toggle-all"></label></th>@endif--}}
            <th class="conv-attachment">&nbsp;</th>
            <th class="conv-subject" colspan="2">
                <span>{{ __("Ticket") }}</span>
            </th>
            @if ($show_assigned)
                <th class="conv-owner">
                    <span>{{ __("Created") }}</span>
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
                &nbsp;
            </th>
            @if (empty($no_customer))
                <th class="conv-customer">
                    <span>{{ __("Status") }}</span>
                </th>
            @endif
            <th class="conv-date">
                <span>
                    {{ __("Last Activity") }}
                </span>
            </th>
          </tr>
        </thead>
        <tbody>
            @foreach ($conversations as $conversation)
                <tr class="conv-row @if (\EndUserPortal::hasNewReplies($conversation)) conv-active @endif" data-conversation_id="{{ $conversation->id }}">
                    @if (empty($no_checkboxes))<td class="conv-current"></td>@endif
                    {{--@if (empty($no_checkboxes))
                        <td class="conv-cb">
                            <input type="checkbox" class="conv-checkbox magic-checkbox" id="cb-{{ $conversation->id }}" name="cb_{{ $conversation->id }}" value="{{ $conversation->id }}"><label for="cb-{{ $conversation->id }}"></label>
                        </td>
                    @endif--}}
                    <td class="conv-attachment">

                        @if ($conversation->has_attachments)
                            <i class="glyphicon glyphicon-paperclip"></i>
                        @else
                            &nbsp;
                        @endif
                    </td>
                    <td class="conv-subject">
                        <a href="{{ \EndUserPortal::ticketUrl($conversation) }}" @if (!empty(request()->x_embed)) target="_blank"@endif>
                            <span class="conv-fader"></span>
                            <p>
                                @if ($conversation->has_attachments)
                                    <i class="conv-attachment-mobile glyphicon glyphicon-paperclip"></i>
                                @endif
                                {{--@if ($conversation->isPhone())
                                    <i class="glyphicon glyphicon-earphone"></i>
                                @endif--}}
                                {{ $conversation->getSubject() }}
                            </p>
                            <p class="conv-preview">@if (!empty($params['show_mailbox']))[{{ $conversation->mailbox_cached->name }}]<br/>@endif{{ '' }}@if ($conversation->preview){{ $conversation->preview }}@else&nbsp;@endif</p>
                        </a>
                    </td>
                    <td class="conv-thread-count">
                        <i class="conv-star glyphicon"></i> <a href="{{ \EndUserPortal::ticketUrl($conversation) }}"><span>{{ $conversation->threads_count }}</span>
                        {{--<a href="{{ \EndUserPortal::ticketUrl($conversation) }}">@if ($conversation->threads_count <= 1)&nbsp;@else<span>{{ $conversation->threads_count }}</span>@endif</a>--}}
                        </a>
                    </td>
                    @if ($show_assigned)
                        <td class="conv-owner">
                            {{-- \EndUserPortal::dateFormat($conversation->created_at) --}}
                        </td>
                    @endif
                    <td class="conv-number">
                        {{--<a href="{{ \EndUserPortal::ticketUrl($conversation) }}">{{ $conversation->threads_count }}</a>--}}
                        <a href="{{ \EndUserPortal::ticketUrl($conversation) }}">@if (\EndUserPortal::hasNewReplies($conversation)) <span class="glyphicon glyphicon-envelope text-help"></span>@endif</a>
                    </td>
                    @if (empty($no_customer))
                        <td class="conv-customer">
                            <a href="{{ \EndUserPortal::ticketUrl($conversation) }}"><small class="text-help">{{ \EndUserPortal::getStatusName($conversation) }}</small></a>
                        </td>
                    @endif
                    <td class="conv-date">
                        <a href="{{ \EndUserPortal::ticketUrl($conversation) }}">{{ \EndUserPortal::dateFormat($conversation->last_reply_at, 'M j, Y') }}</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty-content">
        <i class="glyphicon  glyphicon-ok text-larger"></i>
        <p>{{ __('There are no tickets yet') }}</p>

        <a href="{{ route('enduserportal.submit', ['id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}" class="btn btn-primary margin-bottom eup-btn-create">{{ \EndUserPortal::getMailboxParam($mailbox, 'text_submit') }}</a>
    </div>
@endif