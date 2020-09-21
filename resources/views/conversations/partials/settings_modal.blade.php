<div class="modal fade" tabindex="-1" role="dialog" id="conv-settings-modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{{ __('Conversation History') }}</h4>
            </div>
            <div class="modal-body">
                <form action="">
                    <div class="form-group">
                        <label for="email_history">{{ __('Conversation History') }}</label>

                        <select id="email_history" class="form-control" name="email_history" required autofocus>
                            @foreach(App\Conversation::$email_history_codes as $code)
                                <option value="{{ $code }}">{{ \App\Conversation::getEmailHistoryName($code) }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default button-cancel-settings">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary button-save-settings">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>
