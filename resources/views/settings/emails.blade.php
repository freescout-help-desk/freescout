<form class="form-horizontal margin-top" method="POST" action="">
    {{ csrf_field() }}

    <div class="descr-block">
        <p>{{ __("These settings are used to send system emails (alerts to admin and invitation emails to users).") }}</p>

        <p>
            {!! __("It is recommended to use :%tag_begin%PHP's mail() function:%tag_end%.", ['%tag_begin%' => '<strong>', '%tag_end%' =>'</strong>']) !!}
            {{ __("If you want to send system emails via webmail providers (Gmail, Yahoo, etc), use only SMTP method and make sure that SMTP username is equal to 'Mail From', otherwise webmail provider won't send emails.") }}
        </p>
    </div>

    <div class="form-group{{ $errors->has('settings.mail_from') ? ' has-error' : '' }}">
        <label for="mail_from" class="col-sm-2 control-label">{{ __('Mail From') }}</label>

        <div class="col-sm-6">
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

    <div id="mail_driver_options_smtp" class="mail_driver_options @if ($settings['mail_driver'] != \MailHelper::MAIL_DRIVER_SMTP) hidden @endif margin-top">
        <hr/>
        <div class="form-group{{ $errors->has('settings.mail_host') ? ' has-error' : '' }}">
            <label for="mail_host" class="col-sm-2 control-label">{{ __('SMTP Server') }}</label>

            <div class="col-sm-6">
                <input id="mail_host" type="text" class="form-control input-sized" name="settings[mail_host]" value="{{ old('settings.mail_host', $settings['mail_host']) }}" maxlength="255"  @if ($settings['mail_driver'] == \MailHelper::MAIL_DRIVER_SMTP) required @endif autofocus>
                @if (strstr($settings['mail_host'], '.gmail.'))
                    <div class="form-help">
                        {!! __("Make sure to :%link_start%enable less secure apps:%link_end% in your Google account to send emails from Gmail.", ['%link_start%' => '<a href="https://myaccount.google.com/lesssecureapps?pli=1" target="_blank">', '%link_end%' => '</a>']) !!}
                    </div>
                @endif
                @include('partials/field_error', ['field'=>'settings.mail_host'])
            </div>
        </div>
        <div class="form-group{{ $errors->has('settings.mail_port') ? ' has-error' : '' }}">
            <label for="mail_port" class="col-sm-2 control-label">{{ __('Port') }}</label>

            <div class="col-sm-6">
                <input id="mail_port" type="number" class="form-control input-sized" name="settings[mail_port]" value="{{ old('settings.mail_port', $settings['mail_port']) }}" maxlength="5" @if ($settings['mail_driver'] == \MailHelper::MAIL_DRIVER_SMTP) required @endif autofocus>

                @include('partials/field_error', ['field'=>'settings.mail_port'])
            </div>
        </div>
        <div class="form-group{{ $errors->has('settings.mail_username') ? ' has-error' : '' }}">
            <label for="mail_username" class="col-sm-2 control-label">{{ __('Username') }}</label>

            <div class="col-sm-6">
                <input id="mail_username" type="text" class="form-control input-sized" name="settings[mail_username]" value="{{ old('settings.mail_username', $settings['mail_username']) }}" maxlength="100" @if ($settings['mail_driver'] == \MailHelper::MAIL_DRIVER_SMTP) required @endif autofocus>
                @include('partials/field_error', ['field'=>'settings.mail_username'])
            </div>
        </div>
        <div class="form-group{{ $errors->has('settings.mail_password') ? ' has-error' : '' }}">
            <label for="mail_password" class="col-sm-2 control-label">{{ __('Password') }}</label>

            <div class="col-sm-6">
                <input id="mail_password" type="password" class="form-control input-sized" name="settings[mail_password]" value="{{ old('settings.mail_password', $settings['mail_password']) }}" maxlength="255" @if ($settings['mail_driver'] == \MailHelper::MAIL_DRIVER_SMTP) required @endif autofocus>

                @include('partials/field_error', ['field'=>'settings.mail_password'])
            </div>
        </div>
        <div class="form-group{{ $errors->has('settings.mail_encryption') ? ' has-error' : '' }}">
            <label for="mail_encryption" class="col-sm-2 control-label">{{ __('Encryption') }}</label>

            <div class="col-sm-6">
                <select id="mail_encryption" class="form-control input-sized" name="settings[mail_encryption]" @if ($settings['mail_driver'] == \MailHelper::MAIL_DRIVER_SMTP) required autofocus @endif>
                    <option value="{{ \MailHelper::MAIL_ENCRYPTION_NONE }}" @if (old('settings.mail_encryption', $settings['mail_encryption']) == \MailHelper::MAIL_ENCRYPTION_NONE)selected="selected"@endif>{{ __('None') }}</option>
                    <option value="{{ \MailHelper::MAIL_ENCRYPTION_SSL }}" @if (old('settings.mail_encryption', $settings['mail_encryption']) ==  \MailHelper::MAIL_ENCRYPTION_SSL)selected="selected"@endif>{{ __('SSL') }}</option>
                    <option value="{{ \MailHelper::MAIL_ENCRYPTION_TLS }}" @if (old('settings.mail_encryption', $settings['mail_encryption']) == \MailHelper::MAIL_ENCRYPTION_TLS)selected="selected"@endif>{{ __('TLS') }}</option>
                </select>

                @include('partials/field_error', ['field'=>'settings.mail_encryption'])
            </div>
        </div>
        <hr/>
    </div>

    <div class="form-group">
        <label for="send_test" class="col-sm-2 control-label">{{ __('Send Test To') }}</label>

        <div class="col-sm-6">
            <div class="input-group input-sized">
                <input id="send_test" type="email" class="form-control" value="{{ old('email', \App\Option::get('send_test_to')) }}" maxlength="128">
                <span class="input-group-btn">
                    <button id="send-test-trigger" class="btn btn-default" type="button" data-loading-text="{{ __('Sending') }}…">{{ __('Send Test') }}</button>
                </span>
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

@section('javascript')
    @parent
    mailSettingsInit();
@endsection