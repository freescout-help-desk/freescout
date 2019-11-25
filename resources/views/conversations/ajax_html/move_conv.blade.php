<div class="form-group">
    <label>{{ __('Select Mailbox') }}</label>
	<select type="text" class="form-control input-md move-conv-mailbox-id">
        @foreach ($mailboxes as $mailbox)
            @if ($mailbox->id != $conversation->mailbox_id)
                <option value="{{ $mailbox->id }}">{{ $mailbox->name }} &nbsp;({{ $mailbox->email }})</option>
            @endif
        @endforeach
    </select>
    <label class="margin-top-10">{{ __('or Enter Mailbox Email') }}</label>
	<input type="text" class="form-control move-conv-mailbox-email" />
</div>

<div class="form-group">
	<button class="btn btn-primary btn-move-conv" data-loading-text="{{ __('Moving') }}…" type="submit">{{ __('Move') }}</button>
</div>