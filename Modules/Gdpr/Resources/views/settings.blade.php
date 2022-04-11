<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    <input type="hidden" name="settings[dummy]" value="1" />

    <div class="form-group">
        <label for="url" class="col-sm-2 control-label">{{ __('Delete Emails From Mail Server') }}</label>
        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[gdpr.delete_emails]" value="1" id="gdpr_delete_emails" class="onoffswitch-checkbox" @if (old('settings[gdpr.delete_emails]', $settings['gdpr.delete_emails']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="gdpr_delete_emails"></label>
                    </div>
                </div>
            </div>

            <p class="form-help">
                {{ __('Delete original emails from the mail server when deleting customers.') }}
            </p>
        </div>
    </div>

    <div class="form-group margin-top-0 margin-bottom-0">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary" name="action" value="api_save">
                {{ __('Save') }}
            </button>
        </div>
    </div>

</form>
