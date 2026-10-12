@php
    if (!isset($current_conversation_id)) {
        if (isset($conversation)) {
            $current_conversation_id =  $conversation->id;
        } else {
            $current_conversation_id = -1;
        }
    }
    if (!isset($list_size)) {
        $list_size = App\Conversation::DEFAULT_LIST_SIZE;
    }
@endphp
@foreach ($conversations as $conv_i => $conv)
    @if ($conv_i < $list_size)
        <li class="chat-item @if ($conv->id == $current_conversation_id) active @endif @if ($conv->isActive()) new @endif" data-conv_id="{{ $conv->id }}">
            <a href="{{ $conv->url(null, null, ['chat_mode' => 1]) }}">
                <strong class="folder-name">{{ $conv->customer ? $conv->customer->getFullName(true) : '' }}</strong>
                <span class="active-count pull-right small" data-toggle="tooltip" title="{{ App\User::dateFormat($conv->last_reply_at) }}">{{ \App\User::dateDiffForHumans($conv->last_reply_at) }}</span>
                <br/>
                <span class="chat-preview">{{ $conv->preview }}</span>
                
                @if (!empty($chat_mode))
                    <br/>
                    <span class="chat-tags"><span class="fs-tag fs-tag-sm">{{ $conv->getChannelName() }}</span>@if (!$conv->user_id)<span class="fs-tag fs-tag-sm fs-tag-green">{{ __('Unassigned') }}</span>@endif</span>
                @endif
                
            </a>
        </li>
    @else
        <li>
            <a href="#" class="chats-load-more" data-loading-text="···">
                <i class="glyphicon glyphicon-chevron-down"></i>
            </a>
        </li>
    @endif
@endforeach