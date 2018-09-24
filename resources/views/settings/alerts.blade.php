<form class="form-horizontal margin-top" method="POST" action="">
    {{ csrf_field() }}

    <div class="help-block text-large margin-bottom">
        {{ __("Send email alerts to super admin.") }}
    </div>

    <div class="form-group{{ $errors->has('settings[alert_fetch]') ? ' has-error' : '' }}">
        <label for="alert_fetch" class="col-sm-2 control-label">{{ __('Fetching Problems') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[alert_fetch]" value="1" id="alert_fetch" class="onoffswitch-checkbox" @if (old('settings[alert_fetch]', $settings['alert_fetch']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="alert_fetch"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {{ __('Send alert if application could not fetch emails for a period of time.') }}
            </p>
            <div class="controls form-inline @if (!$settings['alert_fetch']) hidden @endif" id="alert_fetch_period_wrap">
                <label for="alert_fetch_period" class="control-label">{{ __('Period (minutes)') }}</label> 

                <input id="alert_fetch_period" type="number" min="5" class="form-control" name="settings[alert_fetch_period]" value="{{ old('settings[alert_fetch_period]', $settings['alert_fetch_period']) }}" @if ($settings['alert_fetch']) required autofocus @endif>
            </div>
            @include('partials/field_error', ['field'=>'settings.alert_fetch_period'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[alert_send]') ? ' has-error' : '' }}">
        <label for="alert_send" class="col-sm-2 control-label">{{ __('Sending Errors') }} (todo)</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[alert_send]" value="1" id="alert_send" class="onoffswitch-checkbox" @if (old('settings[alert_send]', $settings['alert_send']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="alert_send"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {{ __('Send alert when notification to user or reply to customer can not be sent.') }}
            </p>
            @include('partials/field_error', ['field'=>'settings.alert_send'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[alert_recipients]') ? ' has-error' : '' }}">
        <label for="alert_recipients" class="col-sm-2 control-label">{{ __('Extra Recipients') }}</label>

        <div class="col-sm-6">
            <input id="alert_recipients" type="text" class="form-control input-sized" name="settings[alert_recipients]" value="{{ old('settings[alert_recipients]', $settings['alert_recipients']) }}">
            <p class="help-block">
                {{ __('Comma separated emails of extra recipients.') }}
            </p>
            @include('partials/field_error', ['field'=>'settings.alert_recipients'])
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>