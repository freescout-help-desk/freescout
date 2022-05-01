@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

<div id="editor_bottom_toolbar" style="display:none">
    <div id="editor_signature">
        @if ($mailbox->signature)
            {!! $conversation->getSignatureProcessed([], true) !!}
        @endif
    </div>
    @action('conv_editor.editor_toolbar_prepend', $mailbox, $conversation)
	<span class="editor-btm-text">{{ __('Status') }}:</span> 
    {{-- Note keeps status--}}
	<select name="status" class="form-control parsley-exclude" data-reply-status="{{ $mailbox->ticket_status }}" data-note-status="{{ $conversation->status }}">
        <option value="{{ App\Mailbox::TICKET_STATUS_ACTIVE }}" @if ($mailbox->ticket_status == App\Mailbox::TICKET_STATUS_ACTIVE)selected="selected"@endif>{{ __('Active') }}</option>
        <option value="{{ App\Mailbox::TICKET_STATUS_PENDING }}" @if ($mailbox->ticket_status == App\Mailbox::TICKET_STATUS_PENDING)selected="selected"@endif>{{ __('Pending') }}</option>
        <option value="{{ App\Mailbox::TICKET_STATUS_CLOSED }}" @if ($mailbox->ticket_status == App\Mailbox::TICKET_STATUS_CLOSED)selected="selected"@endif>{{ __('Closed') }}</option>
    </select> 
    <small class="note-bottom-div"></small> 
    <span class="editor-btm-text">{{ __('Assign to') }}:</span> 
    {{-- Note never changes Assignee --}}
    <select name="user_id" class="form-control parsley-exclude">
        <option value="-1" @if ($mailbox->ticket_assignee == App\Mailbox::TICKET_ASSIGNEE_ANYONE)data-default="true" selected="selected"@endif>{{ __('Anyone') }}</option>
    	<option value="{{ Auth::user()->id }}" @if (
            ($conversation->user_id == Auth::user()->id && $mailbox->ticket_assignee != App\Mailbox::TICKET_ASSIGNEE_ANYONE) 
            || (!$conversation->user_id && $mailbox->ticket_assignee == App\Mailbox::TICKET_ASSIGNEE_REPLYING_UNASSIGNED) 
            || $mailbox->ticket_assignee == App\Mailbox::TICKET_ASSIGNEE_REPLYING)data-default="true" selected="selected"@endif>{{ __('Me') }}</option>
        @foreach ($mailbox->usersAssignable() as $user)
            @if ($user->id != Auth::user()->id)
            	<option value="{{ $user->id }}" @if ($conversation->user_id == $user->id && !in_array($mailbox->ticket_assignee, [ App\Mailbox::TICKET_ASSIGNEE_REPLYING, App\Mailbox::TICKET_ASSIGNEE_ANYONE]))data-default="true" selected="selected"@endif @action('assignee_list.option_attrs', $user)>{{ $user->getFullName() }}@action('assignee_list.item_append', $user)</option>
            @endif
        @endforeach
    </select> 

    <input type="hidden" name="after_send" id="after_send" value="{{ $after_send }}" class="parsley-exclude"/>
    <div class="btn-group btn-group-send">
    	<button class="hidden"></button>
        <button type="button" class="btn btn-primary btn-reply-submit btn-send-text" data-loading-text="{{ __('Sending') }}…">@if (empty($new_converstion)){{ __('Send Reply') }}@else{{ __('Send') }}@endif</button>
        <button type="button" class="btn btn-primary btn-reply-submit btn-send-forward" data-loading-text="{{ __('Sending') }}…">{{ __('Forward') }}</button>
        <button type="button" class="btn btn-primary btn-reply-submit btn-add-note-text" data-loading-text="{{ __('Saving') }}…">{{ __('Add Note') }}</button>
        <button type="button" class="btn btn-primary btn-reply-submit btn-create-conv" data-loading-text="{{ __('Creating') }}…">{{ __('Create') }}</button>
        <button type="button" class="btn btn-primary btn-send-menu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><small class="glyphicon glyphicon-chevron-down"></small></button>
        <ul class="dropdown-menu dropdown-menu-right dropdown-after-send">
            <li @if ($after_send == App\MailboxUser::AFTER_SEND_STAY) class="active" @endif><a href="javascript:void(0)" data-after-send="{{ App\MailboxUser::AFTER_SEND_STAY }}">{{ __('Send and stay on page') }}</a></li>
            <li @if ($after_send == App\MailboxUser::AFTER_SEND_NEXT) class="active" @endif><a href="#" data-after-send="{{ App\MailboxUser::AFTER_SEND_NEXT }}">{{ __('Send and next active') }}</a></li>
            <li @if ($after_send == App\MailboxUser::AFTER_SEND_FOLDER) class="active" @endif><a href="#" data-after-send="{{ App\MailboxUser::AFTER_SEND_FOLDER }}">{{ __('Send and back to folder') }}</a></li>
            @if (empty($new_converstion))
                <li class="divider"></li>
                <li><a href="#" class="after-send-change" data-modal-body="#after-send-change-body" data-modal-title="{{ __('Default Redirect') }}" data-no-close-btn="true" data-modal-no-footer="true">{{ __('Change default redirect') }}…</a></li>
            @endif
            @if (empty($new_converstion))
            	<li class="divider"></li>
            	<li><a href="#" data-toggle="modal" data-target="#conv-settings-modal">{{ ucfirst(mb_strtolower(__('Conversation History'))) }}…</a></li>
            @endif
            @action('conversation.append_send_dropdown', $conversation, $mailbox, $new_converstion ?? false)
        </ul>
    </div>
    <div id="after-send-change-body" class="hidden">
        <div class="row-container">
            <div class="row">
                <div class="form-horizontal">
                    <div class="form-group{{ $errors->has('after_send') ? ' has-error' : '' }}">
                        <label for="after_send" class="col-sm-3 control-label">{{ __('After Sending') }}</label>

                        <div class="col-sm-9">
                            <select class="form-control input-sized" name="after_send_default" required autofocus>
                                <option value="{{ App\MailboxUser::AFTER_SEND_STAY }}" @if ($after_send == App\MailboxUser::AFTER_SEND_STAY)selected="selected"@endif>{{ __('Stay on the same page') }}</option>
                                <option value="{{ App\MailboxUser::AFTER_SEND_NEXT }}" @if ($after_send == App\MailboxUser::AFTER_SEND_NEXT)selected="selected"@endif>{{ __('Next active conversation') }}</option>
                                <option value="{{ App\MailboxUser::AFTER_SEND_FOLDER }}" @if ($after_send == App\MailboxUser::AFTER_SEND_FOLDER)selected="selected"@endif>{{ __('Back to folder') }}</option>
                            </select>

                            <p class="block-help">
                                {{ __('This setting gives you control over what page loads after you perform an action (send a reply, add a note, change conversation status or assignee).') }}
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-9 col-sm-offset-3">
                            <button type="submit" class="btn btn-primary" data-loading-text="{{ __('Saving') }}…" onclick="saveAfterSend(this)">
                                {{ __('Save') }}
                            </button>

                            <a href="#" class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
