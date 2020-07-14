<div class="modal fade" tabindex="-1" role="dialog" id="settingsModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{{ __('Conversation settings') }}</h4>
            </div>
            <div class="modal-body">
                <form action="">
                    <div class="form-group">
                        <label for="setting_email_conv_history">{{ __('Conversation History') }}</label>

                        <select id="setting_email_conv_history" class="form-control" name="email_conv_history" required autofocus>
                            <option value="global" @if (old('email_conv_history', $conversation->email_conv_history) == 'global')selected="selected"@endif>{{ __('Use the default settings') }}</option>
                            <option value="none" @if (old('email_conv_history', $conversation->email_conv_history) == 'none')selected="selected"@endif>{{ __('Do not include previous messages') }}</option>
                            <option value="last" @if (old('email_conv_history', $conversation->email_conv_history) == 'last')selected="selected"@endif>{{ __('Include the last message') }}</option>
                            <option value="full" @if (old('email_conv_history', $conversation->email_conv_history) == 'full')selected="selected"@endif>{{ __('Send full conversation history') }}</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" id="save_button" class="btn btn-primary">{{ __('Save and close') }}</button>
            </div>
        </div>
    </div>
</div>

@section('javascript')
    @parent
        var $settingsModal = jQuery('#settingsModal');
        var $historySelect = jQuery('#setting_email_conv_history', $settingsModal);
        jQuery('#save_button', $settingsModal).on('click', function(e) {
            e.preventDefault();
            $settingsModal.modal('hide');
            console.log('sending settings');
            fsAjax(
                {
                    action: 'save_settings',
                    conversation_id: {{ $conversation->id }},
                    email_conv_history: $historySelect.children("option:selected").val(),
                },
                laroute.route('conversations.ajax'),
                function(response) {
                    if (typeof(response.status) != "undefined" && response.status !== 'success') {
                        if (typeof (response.msg) != "undefined") {
                            showFloatingAlert('error', response.msg);
                        } else {
                            showFloatingAlert('error', Lang.get("messages.error_occured"));
                        }
                        loaderHide();
                    }
                },
                true
            );
        });
@append
