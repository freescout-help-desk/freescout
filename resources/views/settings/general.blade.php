<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('settings[company_name]') ? ' has-error' : '' }}">
        <label for="company_name" class="col-sm-2 control-label">{{ __('Company Name') }}</label>

        <div class="col-sm-6">
            <input id="company_name" type="text" class="form-control input-sized" name="settings[company_name]" value="{{ old('settings[company_name]', $settings['company_name']) }}" maxlength="60" required autofocus>

            @include('partials/field_error', ['field'=>'settings.company_name'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[custom_number]') ? ' has-error' : '' }}">
        <label for="custom_number" class="col-sm-2 control-label">{{ __('Conversation Number') }}</label>

        <div class="col-sm-6">

            <div class="controls">
                <label for="custom_number_0" class="radio inline plain"><input type="radio" name="settings[custom_number]" value="false" id="custom_number_0" @if (!$settings['custom_number'])checked="checked"@endif> {{ __('Equal to conversation ID') }}</label>
                <label for="custom_number_1" class="radio inline"><input type="radio" name="settings[custom_number]" value="true" id="custom_number_1" @if ($settings['custom_number'])checked="checked"@endif> {{ __('Custom') }}…</label>
            </div>
            @include('partials/field_error', ['field'=>'settings.custom_number'])
        </div>
    </div>

    <div class="form-group @if (!$settings['custom_number']) hidden @endif{{ $errors->has('settings[next_ticket]') ? ' has-error' : '' }}">
        <label for="next_ticket" class="col-sm-2 control-label">{{ __('Next Conversation #') }}</label>

        <div class="col-sm-6">
            <div class="flexy">
                <input id="next_ticket" type="number" class="form-control input-sized" name="settings[next_ticket]" value="{{ old('settings[next_ticket]', $settings['next_ticket']) }}" {{--required autofocus--}}>

                <i class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-placement="left" data-content="{{ __('This number is not visible to customers. It is only used to track conversations within :app_name', ['app_name' => config('app.name')]) }}"></i>
            </div>

            @include('partials/field_error', ['field'=>'settings.next_ticket'])
        </div>
    </div>

    <div class="form-group margin-top">
        <label for="email" class="col-sm-2 control-label">{{ __('User Permissions') }}</label>

        <div class="col-sm-6">
            @foreach (App\User::getUserPermissionsList() as $permission_id)
                <div class="control-group">
                    <label class="checkbox" for="user_permission_{{ $permission_id }}">
                        <input type="checkbox" name="settings[user_permissions][]" value="{{ $permission_id }}" id="user_permission_{{ $permission_id }}" @if (in_array($permission_id, old('settings[user_permissions]', $settings['user_permissions']))) checked="checked" @endif> {{ App\User::getUserPermissionName($permission_id) }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[locale]') ? ' has-error' : '' }}">
        <label for="locale" class="col-sm-2 control-label">{{ __('Default Language') }}</label>

        <div class="col-sm-6">
            <select id="locale" class="form-control input-sized" name="settings[locale]" required autofocus>
                @include('partials/locale_options', ['selected' => old('settings[locale]', $settings['locale'])])
            </select>

            @include('partials/field_error', ['field'=>'settings.timezone'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[timezone]') ? ' has-error' : '' }}">
        <label for="timezone" class="col-sm-2 control-label">{{ __('Timezone') }}</label>

        <div class="col-sm-6">
            <select id="timezone" class="form-control input-sized" name="settings[timezone]" required autofocus>
                @include('partials/timezone_options', ['current_timezone' => old('settings[timezone]', \Config::get('app.timezone'))])
            </select>

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

    <h3 class="subheader">{{ __('Emails to Customers') }}</h3>

    <div class="form-group{{ $errors->has('settings[email_conv_history]') ? ' has-error' : '' }}">
        <label for="email_conv_history" class="col-sm-2 control-label">{{ __('Conversation History') }}</label>

        <div class="col-sm-6">
            <select id="email_conv_history" class="form-control input-sized" name="settings[email_conv_history]" required autofocus>
                <option value="none" @if (old('settings[email_conv_history]', $settings['email_conv_history']) == 'none')selected="selected"@endif>{{ __('Do not include previous messages') }}</option>
                <option value="last" @if (old('settings[email_conv_history]', $settings['email_conv_history']) == 'last')selected="selected"@endif>{{ __('Include the last message') }}</option>
                <option value="full" @if (old('settings[email_conv_history]', $settings['email_conv_history']) == 'full')selected="selected"@endif>{{ __('Send full conversation history') }}</option>
            </select>

            @include('partials/field_error', ['field'=>'settings.email_conv_history'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings[open_tracking]') ? ' has-error' : '' }}">
        <label for="open_tracking" class="col-sm-2 control-label">{{ __('Open Tracking') }}</label>

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
            <p class="form-help">
                {{ __('Add "Powered by :app_name" footer text to the outgoing emails to invite more developers to the project and make the application better.', ['app_name' => \Config::get('app.name')]) }}
            </p>
            @include('partials/field_error', ['field'=>'settings.email_branding'])
        </div>
    </div>

    <h3 class="subheader">{{ __('Notification Emails to Users') }}</h3>

    <div class="form-group{{ $errors->has('settings[email_user_history]') ? ' has-error' : '' }}">
        <label for="email_user_history" class="col-sm-2 control-label">{{ __('Conversation History') }}</label>

        <div class="col-sm-6">
            <select id="email_user_history" class="form-control input-sized" name="settings[email_user_history]" required autofocus>
                <option value="none" @if (old('settings[email_user_history]', $settings['email_user_history']) == 'none')selected="selected"@endif>{{ __('Do not include previous messages') }}</option>
                <option value="last" @if (old('settings[email_user_history]', $settings['email_user_history']) == 'last')selected="selected"@endif>{{ __('Include the last message') }}</option>
                <option value="full" @if (old('settings[email_user_history]', $settings['email_user_history']) == 'full')selected="selected"@endif>{{ __('Send full conversation history') }}</option>
            </select>

            @include('partials/field_error', ['field'=>'settings.email_user_history'])
        </div>
    </div>

    @action('settings.general.append', $settings, $errors)

    <div class="form-group margin-top">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>
