<form class="form-horizontal margin-top margin-bottom" method="POST" action="">
    {{ csrf_field() }}

    <div class="form-group">
        <label for="workflows_allow_non_admins" class="col-sm-2 control-label">{{ __('workflows::messages.allow_non_admins') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[workflows.allow_non_admins]" value="1" id="workflows_allow_non_admins" class="onoffswitch-checkbox" @if (old('settings[workflows.allow_non_admins]', $settings['workflows.allow_non_admins']))checked="checked"@endif >
                        <label class="onoffswitch-label" for="workflows_allow_non_admins"></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group margin-top">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>
