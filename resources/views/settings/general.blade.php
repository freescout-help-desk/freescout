<form class="form-horizontal margin-top" method="POST" action="">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('settings[company_name]') ? ' has-error' : '' }}">
        <label for="company_name" class="col-sm-2 control-label">{{ __('Company Name') }}</label>

        <div class="col-sm-6">
            <input id="company_name" type="text" class="form-control input-sized" name="settings[company_name]" value="{{ old('settings[company_name]', $settings['company_name']) }}" maxlength="60" required autofocus>

            @include('partials/field_error', ['field'=>'settings.company_name'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[next_ticket]') ? ' has-error' : '' }}">
        <label for="next_ticket" class="col-sm-2 control-label">{{ __('Next Conversation #') }} (todo)</label>

        <div class="col-sm-6">
            <input id="next_ticket" type="number" class="form-control input-sized" name="settings[next_ticket]" value="{{ old('settings[next_ticket]', $settings['next_ticket']) }}" {{--required autofocus--}}>

            @include('partials/field_error', ['field'=>'settings.next_ticket'])
        </div>
    </div>

    <div class="form-group margin-top">
        <label for="email" class="col-sm-2 control-label">{{ __('User Permissions') }}</label>

        <div class="col-sm-6">
            @foreach (App\User::$user_permissions as $permission_id)
                <div class="control-group">
                    <label class="checkbox" for="user_permission_{{ $permission_id }}">
                        <input type="checkbox" name="settings[user_permissions][]" value="{{ $permission_id }}" id="user_permission_{{ $permission_id }}" @if (in_array($permission_id, old('settings[user_permissions]', $settings['user_permissions']))) checked="checked" @endif> {{ App\User::getUserPermissionName($permission_id) }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[email_branding]') ? ' has-error' : '' }}">
        <label for="email_branding" class="col-sm-2 control-label">{{ __('Spread the Word', ['app_name' => \Config::get('app.name')]) }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[email_branding]" value="1" id="email_branding" class="onoffswitch-checkbox" @if (old('settings[email_branding]', $settings['email_branding']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="email_branding"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {{ __('Add "Powered by :app_name" footer text to the outgoing emails to invite more developers to the project and make application better.', ['app_name' => \Config::get('app.name')]) }}
            </p>
            @include('partials/field_error', ['field'=>'settings.email_branding'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[open_tracking]') ? ' has-error' : '' }}">
        <label for="open_tracking" class="col-sm-2 control-label">{{ __('Open Tracking') }} (todo)</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[open_tracking]" value="1" id="open_tracking" class="onoffswitch-checkbox" @if (old('settings[open_tracking]', $settings['open_tracking']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="open_tracking"></label>
                    </div>
                </div>
            </div>
            @include('partials/field_error', ['field'=>'settings.open_tracking'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[enrich_customer_data]') ? ' has-error' : '' }}">
        <label for="enrich_customer_data" class="col-sm-2 control-label">{{ __('Enrich Customer Data') }} (todo)</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[enrich_customer_data]" value="1" id="enrich_customer_data" class="onoffswitch-checkbox" @if (old('settings[enrich_customer_data]', $settings['enrich_customer_data']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="enrich_customer_data"></label>
                    </div>

                    <i class="glyphicon glyphicon-info-sign icon-info icon-info-inline" data-toggle="popover" data-trigger="hover" data-content="{{ __('Auto-update your customers profile with avatars, social, and location data.') }}"></i>
                </div>
            </div>
            @include('partials/field_error', ['field'=>'settings.enrich_customer_data'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[timezone]') ? ' has-error' : '' }}">
        <label for="timezone" class="col-sm-2 control-label">{{ __('Timezone') }}</label>

        <div class="col-sm-6">
            <select id="timezone" disabled="disabled" class="form-control input-sized" name="settings[timezone]" required autofocus>
                @include('partials/timezone_options', ['current_timezone' => old('settings[timezone]', \Config::get('app.timezone'))])
            </select>

            <div class="help-block">
                {{ __('Value is set in .env file using APP_TIMEZONE parameter.') }}
            </div>

            @include('partials/field_error', ['field'=>'settings.timezone'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[time_format]') ? ' has-error' : '' }}">
        <label for="time_format" class="col-sm-2 control-label">{{ __('Time Format') }}</label>

        <div class="col-sm-6">

            <div class="controls">
                <label for="12hour" class="radio inline plain"><input type="radio" name="settings[time_format]" value="{{ App\User::TIME_FORMAT_12 }}" id="12hour" @if (old('settings[time_format]', $settings['time_format']) == App\User::TIME_FORMAT_12)checked="checked"@endif> {{ __('12-hour clock (e.g. 2:13pm)') }}</label>
                <label for="24hour" class="radio inline"><input type="radio" name="settings[time_format]" value="{{ App\User::TIME_FORMAT_24 }}" id="24hour" @if (old('settings[time_format]', $settings['time_format']) == App\User::TIME_FORMAT_24 || !$settings['time_format'])checked="checked"@endif> {{ __('24-hour clock (e.g. 14:13)') }}</label>
            </div>
            @include('partials/field_error', ['field'=>'settings.time_format'])
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
