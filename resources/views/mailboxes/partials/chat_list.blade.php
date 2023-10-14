@if (isset($folder))
	<li>
	    <a href="{{ __(route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id, 'chat_mode' => 0])) }}"><i class="glyphicon glyphicon-phone"></i> <span class="folder-name">{{ __('Chats') }} (<i>{{ __('Exit') }}</i>)</span></a>
	</li>
@elseif (!empty($is_in_chat_mode))
	{{-- Chats page --}}
	<li>
	    <a href="{{ __(route('mailboxes.view', ['id' => $mailbox->id, 'chat_mode' => 0])) }}"><i class="glyphicon glyphicon-phone"></i> <span class="folder-name">{{ __('Chats') }} (<i>{{ __('Exit') }}</i>)</span></a>
	</li>
@endif

@php
	$chats = \App\Conversation::getChats($mailbox->id, $offset ?? 0);
@endphp
@foreach ($chats as $chat_i => $chat)
	@if ($chat_i < App\Conversation::CHATS_LIST_SIZE)
	    <li class="chat-item @if (isset($conversation) && $chat->id == $conversation->id) active @endif @if ($chat->isActive()) new @endif" data-chat_id="{{ $chat->id }}">
	        <a href="{{ $chat->url(null, null, ['chat_mode' => 1]) }}">
	        	<strong class="folder-name">{{ $chat->customer->getFullName(true) }}</strong>
	            <span class="active-count pull-right small" data-toggle="tooltip" title="{{ App\User::dateFormat($chat->last_reply_at) }}">{{ \App\User::dateDiffForHumans($chat->last_reply_at) }}</span>
	            <br/>
	            <span class="chat-preview">{{ $chat->preview }}</span>
	            
	            <br/>
	            <span class="chat-tags"><span class="fs-tag fs-tag-sm">{{ $chat->getChannelName() }}</span>@if (!$chat->user_id)<span class="fs-tag fs-tag-sm fs-tag-green">{{ __('Unassigned') }}</span>@endif</span>
		        
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
