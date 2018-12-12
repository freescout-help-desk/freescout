<form class="form-horizontal margin-top" method="POST" action="">
    {{ csrf_field() }}

    <div class="descr-block">
        {{ __("Send email alerts to the administrators.") }}
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
                <label for="alert_fetch_period" class="control-label">{{ __('Check Period (minutes)') }}</label> 

                <input id="alert_fetch_period" type="number" min="5" class="form-control" name="settings[alert_fetch_period]" value="{{ old('settings[alert_fetch_period]', $settings['alert_fetch_period']) }}" @if ($settings['alert_fetch']) required autofocus @endif>
            </div>
            @include('partials/field_error', ['field'=>'settings.alert_fetch_period'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[alert_logs]') ? ' has-error' : '' }}">
        <label for="alert_logs" class="col-sm-2 control-label">{{ __('Logs Monitoring') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[alert_logs]" value="1" id="alert_logs" class="onoffswitch-checkbox" @if (old('settings[alert_logs]', $settings['alert_logs']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="alert_logs"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {{ __('Send new log records by email.') }}
            </p>
            <div class="controls @if (!$settings['alert_logs']) hidden @endif">
                <label class="control-label">{{ __('Logs to monitor') }}:</label> 
                @foreach ($logs as $log)
                    <div class="control-group">
                        <label class="checkbox" for="log_{{ $log }}">
                            <input type="checkbox" name="settings[alert_logs_names][]" value="{{ $log }}" id="log_{{ $log }}" @if (in_array($log, old('settings[alert_logs_names]', $settings['alert_logs_names']))) checked="checked" @endif> {{ App\ActivityLog::getLogTitle($log) }}
                        </label>
                    </div>
                @endforeach
                <div class="control-group form-inline margin-top-10">
                    <label for="alert_logs_period" class="control-label">{{ __('Check Frequency') }}</label> 
                    <select name="settings[alert_logs_period]" class="form-control">
                        <option value="hour" @if (old('settings[alert_logs_period]', $settings['alert_logs_period']) == 'hour') selected @endif>{{ __('Hourly') }}</option>
                        <option value="day" @if (old('settings[alert_logs_period]', $settings['alert_logs_period']) == 'day') selected @endif>{{ __('Daily') }}</option>
                        <option value="week" @if (old('settings[alert_logs_period]', $settings['alert_logs_period']) == 'week') selected @endif>{{ __('Weekly') }}</option>
                        <option value="month" @if (old('settings[alert_logs_period]', $settings['alert_logs_period']) == 'month') selected @endif>{{ __('Monthly') }}</option>
                    </select>
                </div>
            </div>
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