<div class="form-group">
    <input type="hidden" name="default_redirect_mailbox_id" value="{{ $mailbox_id }}" />

    <div class="margin-bottom-5">{{ __('This setting gives you control over what page loads after you perform an action (send a reply, add a note, change conversation status or assignee).') }}</div>

    <select class="form-control" name="after_send_default" required autofocus>
        <option value="{{ App\MailboxUser::AFTER_SEND_STAY }}" @if ($after_send == App\MailboxUser::AFTER_SEND_STAY)selected="selected"@endif>{{ __('Stay on the same page') }}</option>
        <option value="{{ App\MailboxUser::AFTER_SEND_NEXT }}" @if ($after_send == App\MailboxUser::AFTER_SEND_NEXT)selected="selected"@endif>{{ __('Next active conversation') }}</option>
        <option value="{{ App\MailboxUser::AFTER_SEND_FOLDER }}" @if ($after_send == App\MailboxUser::AFTER_SEND_FOLDER)selected="selected"@endif>{{ __('Back to folder') }}</option>
    </select>
</div>

<div class="form-group">
    <button type="submit" class="btn btn-primary" data-loading-text="{{ __('Saving') }}…" onclick="saveAfterSend(this)">
        {{ __('Save') }}
    </button>

    <a href="#" class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</a>
</div>