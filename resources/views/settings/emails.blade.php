<form class="form-horizontal margin-top" method="POST" action="">
    {{ csrf_field() }}

    <div class="help-block text-large margin-bottom">
        {{ __("These settings are used to send system alerts to admin and invitation emails to users.") }}
    </div>

    <div class="form-group{{ $errors->has('settings.mail_from') ? ' has-error' : '' }}">
        <label for="mail_from" class="col-sm-2 control-label">{{ __('Mail From') }}</label>

        <div class="col-md-6">
            <input id="mail_from" type="email" class="form-control input-sized" name="settings[mail_from]" value="{{ old('settings.mail_from', $settings['mail_from']) }}" required autofocus>

            @include('partials/field_error', ['field'=>'settings.mail_from'])
        </div>
    </div>

    <div class="form-group margin-top">
        <label for="email" class="col-sm-2 control-label">{{ __('Send Method') }}</label>

        <div class="col-sm-6">
            <select name="settings[mail_driver]" class="form-control input-sized">
                @foreach ($mail_drivers as $mail_driver => $mail_driver_title)
                    <option value="{{ $mail_driver }}" @if ($settings['mail_driver'] == $mail_driver) selected="selected" @endif>{{ $mail_driver_title }}</option>
                @endforeach
            </select>
           <div id="mail_driver_options_sendmail" class="mail_driver_options text-help padding-top-0 @if ($settings['mail_driver'] != 'sendmail') hidden @endif">
                <strong>{{ __("PHP sendmail path:") }}</strong> {{ $sendmail_path }}
            </div>
           
        </div>
    </div>

    <div id="mail_driver_options_smtp" class="mail_driver_options @if ($settings['mail_driver'] != 'smtp') hidden @endif margin-top">
        <hr/>
        <div class="form-group{{ $errors->has('settings.mail_host') ? ' has-error' : '' }}">
            <label for="mail_host" class="col-sm-2 control-label">{{ __('SMTP Server') }}</label>

            <div class="col-md-6">
                <input id="mail_host" type="text" class="form-control input-sized" name="settings[mail_host]" value="{{ old('settings.mail_host', $settings['mail_host']) }}" maxlength="255"  @if ($settings['mail_driver'] == 'smtp') required @endif autofocus>

                @include('partials/field_error', ['field'=>'settings.mail_host'])
            </div>
        </div>
        <div class="form-group{{ $errors->has('settings.mail_port') ? ' has-error' : '' }}">
            <label for="mail_port" class="col-sm-2 control-label">{{ __('Port') }}</label>

            <div class="col-md-6">
                <input id="mail_port" type="number" class="form-control input-sized" name="settings[mail_port]" value="{{ old('settings.mail_port', $settings['mail_port']) }}" maxlength="5" @if ($settings['mail_driver'] == 'smtp') required @endif autofocus>

                @include('partials/field_error', ['field'=>'settings.mail_port'])
            </div>
        </div>
        <div class="form-group{{ $errors->has('settings.mail_username') ? ' has-error' : '' }}">
            <label for="mail_username" class="col-sm-2 control-label">{{ __('Username') }}</label>

            <div class="col-md-6">
                <input id="mail_username" type="text" class="form-control input-sized" name="settings[mail_username]" value="{{ old('settings.mail_username', $settings['mail_username']) }}" maxlength="100" @if ($settings['mail_driver'] == 'smtp') required @endif autofocus>

                @include('partials/field_error', ['field'=>'settings.mail_username'])
            </div>
        </div>
        <div class="form-group{{ $errors->has('settings.mail_password') ? ' has-error' : '' }}">
            <label for="mail_password" class="col-sm-2 control-label">{{ __('Password') }}</label>

            <div class="col-md-6">
                <input id="mail_password" type="password" class="form-control input-sized" name="settings[mail_password]" value="{{ old('settings.mail_password', $settings['mail_password']) }}" maxlength="255" @if ($settings['mail_driver'] == 'smtp') required @endif autofocus>

                @include('partials/field_error', ['field'=>'settings.mail_password'])
            </div>
        </div>
        <div class="form-group{{ $errors->has('settings.mail_encryption') ? ' has-error' : '' }}">
            <label for="mail_encryption" class="col-sm-2 control-label">{{ __('Encryption') }}</label>

            <div class="col-md-6">
                <select id="mail_encryption" class="form-control input-sized" name="settings[mail_encryption]" @if ($settings['mail_driver'] == 'smtp') required @endif autofocus>
                    <option value="{{ App\Mailbox::OUT_ENCRYPTION_NONE }}" @if (old('settings.mail_encryption', $settings['mail_encryption']) == 'none')selected="selected"@endif>{{ __('None') }}</option>
                    <option value="{{ App\Mailbox::OUT_ENCRYPTION_SSL }}" @if (old('settings.mail_encryption', $settings['mail_encryption']) == 'ssl')selected="selected"@endif>{{ __('SSL') }}</option>
                    <option value="{{ App\Mailbox::OUT_ENCRYPTION_TLS }}" @if (old('settings.mail_encryption', $settings['mail_encryption']) == 'tls')selected="selected"@endif>{{ __('TLS') }}</option>
                </select>

                @include('partials/field_error', ['field'=>'settings.mail_encryption'])
            </div>
        </div>
        <hr/>
    </div>

    <div class="form-group">
        <div class="col-md-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>

@section('javascript')
    @parent
    mailSettingsInit();
@endsection