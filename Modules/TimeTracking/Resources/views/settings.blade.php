<form class="form-horizontal margin-top margin-bottom" method="POST" action="" id="slack_form">
    {{ csrf_field() }}

    {{--<div class="descr-block">
        <p>{{ __("These settings are used to send system emails (alerts to admin and invitation emails to users).") }}</p>
    </div>--}}

    <div class="form-group">
        <label for="" class="col-sm-2 control-label">{{ __('Tracking Mode') }}</label>

        <div class="col-sm-6">
            <div class="control-group">
                <label class="radio" for="mode_{{ TimeTracking::MODE_NORMAL }}">
                    <input type="radio" name="settings[timetracking.mode]" id="mode_{{ TimeTracking::MODE_NORMAL }}" value="{{ TimeTracking::MODE_NORMAL }}" @if (old('settings[timetracking.mode]', $settings['timetracking.mode']) == TimeTracking::MODE_NORMAL) checked="checked" @endif> {{ __('The timer starts when the conversation is assigned to the user.') }}
                </label>
            </div>
            <div class="control-group">
                <label class="radio" for="mode_{{ TimeTracking::MODE_ON_VIEW }}">
                    <input type="radio" name="settings[timetracking.mode]" id="mode_{{ TimeTracking::MODE_ON_VIEW }}" value="{{ TimeTracking::MODE_ON_VIEW }}" @if (old('settings[timetracking.mode]', $settings['timetracking.mode']) == TimeTracking::MODE_ON_VIEW) checked="checked" @endif> {{ __('The timer starts when the user views the conversation, the timer pauses when the user navigates away from the conversation.') }}
                </label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="timelog_dialog" class="col-sm-2 control-label">{{ __("Show Review Time Dialog") }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[timetracking.timelog_dialog]" value="1" id="timelog_dialog" class="onoffswitch-checkbox" @if (old('settings[timetracking.timelog_dialog]', $settings['timetracking.timelog_dialog']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="timelog_dialog"></label>
                    </div>
                </div>
            </div>
            <p class="form-help">
                {{ __('Users can review and edit their time before the timelog is saved (when the conversation is closed or assigned to another user).') }}
            </p>
        </div>
    </div>

    <div class="form-group">
        <label for="interactive" class="col-sm-2 control-label">{{ __('Display Timer Controls') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[timetracking.interactive]" value="1" id="interactive" class="onoffswitch-checkbox" @if (old('settings[timetracking.interactive]', $settings['timetracking.interactive']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="interactive"></label>
                    </div>
                </div>
            </div>
            <p class="form-help">
                {{ __('Users can manually pause or resume the timer.') }}
            </p>
        </div>
    </div>

    <div class="form-group">
        <label for="allow_reset" class="col-sm-2 control-label">{{ __('Allow to Reset Timer') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[timetracking.allow_reset]" value="1" id="allow_reset" class="onoffswitch-checkbox" @if (old('settings[timetracking.allow_reset]', $settings['timetracking.allow_reset']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="allow_reset"></label>
                    </div>
                </div>
            </div>
            <p class="form-help">
                {{ __('Allow users to reset the current time spent on a ticket.') }}
            </p>
        </div>
    </div>

    <div class="form-group">
        <label for="show_timelogs" class="col-sm-2 control-label">{{ __('Display Timelogs to Users') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[timetracking.show_timelogs]" value="1" id="show_timelogs" class="onoffswitch-checkbox" @if (old('settings[timetracking.show_timelogs]', $settings['timetracking.show_timelogs']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="show_timelogs"></label>
                    </div>
                </div>
            </div>
            <p class="form-help">
                {{ __('Allow users to view timelogs in conversations. Admin always can see timelogs.') }}
            </p>
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>