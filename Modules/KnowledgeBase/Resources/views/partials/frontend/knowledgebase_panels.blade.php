@if (count($mailboxes))
    @foreach($mailboxes as $mailbox)
        <a href="{{ \Kb::getKbUrl($mailbox) }}" class="kb-mailbox-panel">
            <div class="kb-mailbox-panel-title">{{ $mailbox->name }}</div>
        </a>
    @endforeach
@endif
