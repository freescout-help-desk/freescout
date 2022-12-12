@if ($thread->has_attachments)
    <div class="thread-attachments">
        <i class="glyphicon glyphicon-paperclip"></i>
        <ul>
            @foreach ($thread->attachments as $attachment)
                <li data-attachment-id="{{ $attachment->id }}">
                    <a href="{{ $attachment->url() }}" class="attachment-link break-words" target="_blank">{{ $attachment->file_name }}</a>
                    <span class="text-help">({{ $attachment->getSizeName() }})</span>
                    <a href="{{ $attachment->url() }}" download><i class="glyphicon glyphicon-download-alt small"></i></a>
                    @action('thread.attachment_append', $attachment, $thread, $conversation, $mailbox)
                </li>
            @endforeach
            @action('thread.attachments_list_append', $thread, $conversation, $mailbox)
        </ul>
    </div>
@endif
