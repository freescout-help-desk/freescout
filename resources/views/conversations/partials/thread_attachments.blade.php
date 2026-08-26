@if ($thread->has_attachments)
    @php
        $audio_player_enabled = \Option::get('audio_player');
    @endphp
    <div class="thread-attachments">
        <i class="glyphicon glyphicon-paperclip"></i>
        <ul>
            @foreach ($thread->attachments as $attachment)
                @php
                    $show_audio_player = $audio_player_enabled && $attachment->isPlayableAudio();
                @endphp
                <li data-attachment-id="{{ $attachment->id }}" data-mime="{{ $attachment->mime_type }}" @if ($show_audio_player)class="attachment-with-player"@endif>
                    <a href="{{ $attachment->url() }}" class="attachment-link break-words" target="_blank">{{ $attachment->file_name }}</a>
                    <span class="text-help">({{ $attachment->getSizeName() }})</span>
                    <a href="{{ $attachment->url() }}" download><i class="glyphicon glyphicon-download-alt small"></i></a>
                    @action('thread.attachment_append', $attachment, $thread, $conversation, $mailbox)
                    @if ($show_audio_player)
                        {{-- preload="none" so opening a conversation does not fetch every audio file. --}}
                        <audio class="attachment-player" controls preload="none" src="{{ $attachment->url() }}"></audio>
                    @endif
                </li>
            @endforeach
            @action('thread.attachments_list_append', $thread, $conversation, $mailbox)
        </ul>
    </div>
@endif
