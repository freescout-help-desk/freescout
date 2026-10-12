@if (isset($folder))
	<li>
	    <a href="{{ __(route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id, 'chat_mode' => 0])) }}"><i class="glyphicon glyphicon-chevron-left"></i> <span class="folder-name">{{ __('Chats') }} (<strong>{{ __('Exit') }}</strong>)</span></a>
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
@include('conversations/partials/conv_list', ['conversations' => $chats, 'list_size' => App\Conversation::CHATS_LIST_SIZE, 'chat_mode' => true])

