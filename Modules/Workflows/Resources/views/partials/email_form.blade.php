<div class="row-container">
    <form class="form-horizontal wf-email-form @if (!empty($is_note)) conv-note-block @endif" method="POST" action="">
        {{--@if (!in_array('author', $exclude_fields ?? []))
            <div class="form-group wf-email-form-author">
                <label class="control-label conv-reply-label">{{ __('Author') }}</label>
                <div class="conv-reply-field">
                    <select class="form-control wf-email-input" name="author" required>
                        @foreach ($mailbox->usersHavingAccess() as $user_item)
                            <option value="{{ $user_item->id }}">{{ $user_item->getFullName() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif--}}
        @if (!in_array('to', $exclude_fields ?? []))
            <div class="form-group wf-email-form-to">
                <label class="control-label conv-reply-label">{{ __('To') }}</label>
                <div class="conv-reply-field">
                    <input class="form-control wf-email-input" type="text" value="" name="to" required/>
                </div>
            </div>
        @endif
        @if (!in_array('cc', $exclude_fields ?? []))
            <div class="form-group wf-email-form-cc">
                <label class="control-label conv-reply-label">Cc</label>
                <div class="conv-reply-field">
                    <input class="form-control wf-email-input" type="text" value="" name="cc" />
                </div>
            </div>
        @endif
        @if (!in_array('bcc', $exclude_fields ?? []))
            <div class="form-group wf-email-form-bcc">
                <label class="control-label conv-reply-label">Bcc</label>
                <div class="conv-reply-field">
                    <input class="form-control wf-email-input" type="text" value="" name="bcc" />
                </div>
            </div>
        @endif
        <div class="form-group wf-email-form-body">
            <textarea class="wf-email-form-editor form-control wf-email-input" name="body" rows="8"></textarea>
            @if (!in_array('no_signature', $exclude_fields ?? []))
                <div>
                    <label class="checkbox">
                        <input type="checkbox" class="wf-email-input" name="no_signature" value="1"> {{ __('Do not include signature') }}
                    </label>
                </div>
            @endif
        </div>

        <div class="form-group margin-top margin-bottom-10">
            <button class="btn btn-primary" data-loading-text="{{ __('Saving') }}…">{{ __('Save') }}</button> 
            <button class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
        </div>
    </form>
</div>