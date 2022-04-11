<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-6">
            <p class="text-help">{{ __("Auto replies will not be sent to the specified noreply emails.") }}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Custom Noreply Emails') }}</label>

        <div class="col-sm-6">
            <textarea class="form-control input-sized-lg" rows="5" name="settings[noreply.emails_custom]">{{ implode("\n", $settings['noreply.emails_custom']) }}</textarea>
            <p class="form-help">
                {{ __('Enter email prefixes without @ sign one per line. Use a dash where you want to allow a dash or no symbol.') }}
            </p>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ __('Default Noreply Emails') }}</label>

        <div class="col-sm-6">
            <textarea class="form-control input-sized-lg" rows="5" disabled>{{ implode("\n", $settings['noreply.emails_default']) }}</textarea>
        </div>
    </div>

    <div class="form-group margin-bottom-30 margin-top-25">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary" name="action">
                {{ __('Save') }}
            </button>
        </div>
    </div>

</form>
