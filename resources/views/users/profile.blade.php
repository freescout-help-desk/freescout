@extends('layouts.app')

@section('title_full', __('Edit User').' - '.$user->getFullName())

@section('sidebar')
    @include('partials/sidebar_menu_toggle')

    <div class="dropdown sidebar-title">
        <span class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
            {{ $user->emails }} {{ $user->last_name }} @if (count($users))<span class="caret"></span>@endif
        </span>
        @if (count($users))
            <ul class="dropdown-menu">
                @foreach ($users as $user_item)
                    <li @if ($user_item->id == $user->id)class="active"@endif><a href="{{ route('users.profile', ['id'=>$user_item->id]) }}">{{ $user_item->emails }} {{ $user_item->last_name }}</a></li>
                @endforeach
            </ul>
        @endif
    </div>

    @include('partials/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Profile') }}
    </div>

    @include('partials/flash_messages')

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="">
                    {{ csrf_field() }}

                    <div class="form-group{{ $errors->has('role') ? ' has-error' : '' }}">
                        <label for="role" class="col-sm-2 control-label">{{ __('Role') }}</label>

                        <div class="col-md-6 flexy">
                            <select id="role" type="text" class="form-control input-sized" name="role" required autofocus>
                                <option value="{{ App\User::ROLE_USER }}" @if (old('role', $user->role) == App\User::ROLE_USER)selected="selected"@endif>{{ __('User') }}</option>
                                <option value="{{ App\User::ROLE_ADMIN }}" @if (old('role', $user->role) == App\User::ROLE_ADMIN)selected="selected"@endif>{{ __('Administrator') }}</option>
                            </select>

                            <i class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="ture" data-placement="left" data-title="{{ __('Roles') }}" data-content="{{ __('<strong>Administrators</strong> can create new users and have access to all mailboxes and settings <br><br><strong>Users</strong> have access to the mailbox(es) specified in their permissions') }}"></i>

                            @include('partials/field_error', ['field'=>'role'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
                        <label for="first_name" class="col-sm-2 control-label">{{ __('First Name') }}</label>

                        <div class="col-md-6">
                            <input id="first_name" type="text" class="form-control input-sized" name="first_name" value="{{ old('first_name', $user->first_name) }}" maxlength="20" required autofocus>

                            @include('partials/field_error', ['field'=>'first_name'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
                        <label for="last_name" class="col-sm-2 control-label">{{ __('Last Name') }}</label>

                        <div class="col-md-6">
                            <input id="last_name" type="text" class="form-control input-sized" name="last_name" value="{{ old('last_name', $user->last_name) }}" maxlength="30" required autofocus>

                            @include('partials/field_error', ['field'=>'last_name'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        <label for="email" class="col-sm-2 control-label">{{ __('Email') }}</label>

                        <div class="col-md-6">
                            <input id="email" type="email" class="form-control input-sized" name="email" value="{{ old('email', $user->email) }}" maxlength="100" required autofocus>

                            @include('partials/field_error', ['field'=>'email'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('emails') ? ' has-error' : '' }}">
                        <label for="emails" class="col-sm-2 control-label">{{ __('Alternate Emails') }}</label>

                        <div class="col-md-6">
                            <input id="emails" type="text" class="form-control input-sized" name="emails" value="{{ old('emails', $user->emails) }}" placeholder="{{ __('(optional)') }}" maxlength="100">

                            @include('partials/field_error', ['field'=>'emails'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('job_title') ? ' has-error' : '' }}">
                        <label for="job_title" class="col-sm-2 control-label">{{ __('Job Title') }}</label>

                        <div class="col-md-6">
                            <input id="job_title" type="text" class="form-control input-sized" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="{{ __('(optional)') }}" maxlength="100">

                            @include('partials/field_error', ['field'=>'job_title'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('phone') ? ' has-error' : '' }}">
                        <label for="phone" class="col-sm-2 control-label">{{ __('Phone Number') }}</label>

                        <div class="col-md-6">
                            <input id="phone" type="text" class="form-control input-sized" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="{{ __('(optional)') }}" maxlength="60">

                            @include('partials/field_error', ['field'=>'phone'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('timezone') ? ' has-error' : '' }}">
                        <label for="timezone" class="col-sm-2 control-label">{{ __('Timezone') }} (todo)</label>

                        <div class="col-md-6">
                            <select id="timezone" class="form-control input-sized" name="timezone" required autofocus>
                                <option value="Pacific/Honolulu" @if (old('role', $user->timezone) == 'Pacific/Honolulu')selected="selected"@endif>(GMT-10:00) Hawaiian/Aleutian Time</option>
                                <option value="America/Anchorage" @if (old('role', $user->timezone) == 'America/Anchorage')selected="selected"@endif>(GMT-08:00) Alaska Time</option>
                                <option value="America/Los_Angeles" @if (old('role', $user->timezone) == 'America/Los_Angeles')selected="selected"@endif>(GMT-07:00) Pacific Time (US)</option>
                                <option value="America/Phoenix" @if (old('role', $user->timezone) == 'America/Phoenix')selected="selected"@endif>(GMT-07:00) Mountain Time (Arizona)</option>
                                <option value="America/Denver" @if (old('role', $user->timezone) == 'America/Denver')selected="selected"@endif>(GMT-06:00) Mountain Time (US)</option>
                                <option value="America/Chicago" @if (old('role', $user->timezone) == 'America/Chicago')selected="selected"@endif>(GMT-05:00) Central Time (US)</option>
                                <option value="America/New_York" @if (old('role', $user->timezone) == 'America/New_York')selected="selected"@endif>(GMT-04:00) Eastern Time (US)</option>
                                <option value="--SEPARATOR1--" disabled="disabled" @if (old('role', $user->timezone) == '--SEPARATOR1--" disabled="disabled')selected="selected"@endif>-------------</option>
                                <option value="America/Indiana/Knox" @if (old('role', $user->timezone) == 'America/Indiana/Knox')selected="selected"@endif>(GMT-05:00) Central Time (Indiana)</option>
                                <option value="America/Indiana/Indianapolis" @if (old('role', $user->timezone) == 'America/Indiana/Indianapolis')selected="selected"@endif>(GMT-04:00) Eastern Time (Indiana)</option>
                                <option value="--SEPARATOR2--" disabled="disabled" @if (old('role', $user->timezone) == '--SEPARATOR2--" disabled="disabled')selected="selected"@endif>-------------</option>
                                <option value="America/Regina" @if (old('role', $user->timezone) == 'America/Regina')selected="selected"@endif>(GMT-06:00) Central Time (Saskatchewan)</option>
                                <option value="America/Monterrey" @if (old('role', $user->timezone) == 'America/Monterrey')selected="selected"@endif>(GMT-05:00) Central Time (Mexico City, Monterey)</option>
                                <option value="America/Lima" @if (old('role', $user->timezone) == 'America/Lima')selected="selected"@endif>(GMT-05:00) UTC/GMT -5 hours</option>
                                <option value="America/Manaus" @if (old('role', $user->timezone) == 'America/Manaus')selected="selected"@endif>(GMT-04:00) Atlantic Time</option>
                                <option value="America/Puerto_Rico" @if (old('role', $user->timezone) == 'America/Puerto_Rico')selected="selected"@endif>(GMT-04:00) Atlantic Time (Puerto Rico)</option>
                                <option value="America/Thule" @if (old('role', $user->timezone) == 'America/Thule')selected="selected"@endif>(GMT-03:00) Western Greenland Time</option>
                                <option value="America/Sao_Paulo" @if (old('role', $user->timezone) == 'America/Sao_Paulo')selected="selected"@endif>(GMT-03:00) Eastern Brazil</option>
                                <option value="America/St_Johns" @if (old('role', $user->timezone) == 'America/St_Johns')selected="selected"@endif>(GMT-02:30) Newfoundland Time</option>
                                <option value="America/Godthab" @if (old('role', $user->timezone) == 'America/Godthab')selected="selected"@endif>(GMT-02:00) Central Greenland Time</option>
                                <option value="Etc/GMT+2" @if (old('role', $user->timezone) == 'Etc/GMT+2')selected="selected"@endif>(GMT-02:00) GMT-2:00</option>
                                <option value="America/Scoresbysund" @if (old('role', $user->timezone) == 'America/Scoresbysund')selected="selected"@endif>(GMT+00:00) Eastern Greenland Time</option>
                                <option value="Atlantic/Reykjavik" @if (old('role', $user->timezone) == 'Atlantic/Reykjavik')selected="selected"@endif>(GMT+00:00) Western European Time (Iceland)</option>
                                <option value="UTC" @if (old('role', $user->timezone) == 'UTC')selected="selected"@endif>(GMT+00:00) UTC</option>
                                <option value="Europe/London" @if (old('role', $user->timezone) == 'Europe/London')selected="selected"@endif>(GMT+01:00) British Time (London)</option>
                                <option value="Etc/GMT-1" @if (old('role', $user->timezone) == 'Etc/GMT-1')selected="selected"@endif>(GMT+01:00) GMT+1:00</option>
                                <option value="Europe/Lisbon" @if (old('role', $user->timezone) == 'Europe/Lisbon')selected="selected"@endif>(GMT+01:00) Western European Time (Lisbon)</option>
                                <option value="Europe/Paris" @if (old('role', $user->timezone) == 'Europe/Paris')selected="selected"@endif>(GMT+02:00) Western European Time</option>
                                <option value="Europe/Berlin" @if (old('role', $user->timezone) == 'Europe/Berlin')selected="selected"@endif>(GMT+02:00) Central European Time</option>
                                <option value="Europe/Bucharest" @if (old('role', $user->timezone) == 'Europe/Bucharest')selected="selected"@endif>(GMT+03:00) Eastern European Time</option>
                                <option value="Africa/Johannesburg" @if (old('role', $user->timezone) == 'Africa/Johannesburg')selected="selected"@endif>(GMT+02:00) South Africa Standard Time</option>
                                <option value="Africa/Kampala" @if (old('role', $user->timezone) == 'Africa/Kampala')selected="selected"@endif>(GMT+03:00) Eastern Africa Time</option>
                                <option value="Etc/GMT-3" @if (old('role', $user->timezone) == 'Etc/GMT-3')selected="selected"@endif>(GMT+03:00) Moscow</option>
                                <option value="Asia/Tehran" @if (old('role', $user->timezone) == 'Asia/Tehran')selected="selected"@endif>(GMT+04:30) Iran Standard Time</option>
                                <option value="Asia/Dubai" @if (old('role', $user->timezone) == 'Asia/Dubai')selected="selected"@endif>(GMT+04:00) UAE (Dubai)</option>
                                <option value="Asia/Karachi" @if (old('role', $user->timezone) == 'Asia/Karachi')selected="selected"@endif>(GMT+05:00) Pakistan Standard Time (Karachi)</option>
                                <option value="Asia/Calcutta" @if (old('role', $user->timezone) == 'Asia/Calcutta')selected="selected"@endif>(GMT+05:30) India</option>
                                <option value="Asia/Dhaka" @if (old('role', $user->timezone) == 'Asia/Dhaka')selected="selected"@endif>(GMT+06:00) Bangladesh Standard Time</option>
                                <option value="Asia/Jakarta" @if (old('role', $user->timezone) == 'Asia/Jakarta')selected="selected"@endif>(GMT+07:00) Western Indonesian Time (Jakarta)</option>
                                <option value="Asia/Bangkok" @if (old('role', $user->timezone) == 'Asia/Bangkok')selected="selected"@endif>(GMT+07:00) Thailand (Bangkok)</option>
                                <option value="Asia/Hong_Kong" @if (old('role', $user->timezone) == 'Asia/Hong_Kong')selected="selected"@endif>(GMT+08:00) Hong Kong</option>
                                <option value="Asia/Singapore" @if (old('role', $user->timezone) == 'Asia/Singapore')selected="selected"@endif>(GMT+08:00) Singapore</option>
                                <option value="Australia/West" @if (old('role', $user->timezone) == 'Australia/West')selected="selected"@endif>(GMT+08:00) Australian Western Time</option>
                                <option value="Asia/Tokyo" @if (old('role', $user->timezone) == 'Asia/Tokyo')selected="selected"@endif>(GMT+09:00) Tokyo</option>
                                <option value="Australia/North" @if (old('role', $user->timezone) == 'Australia/North')selected="selected"@endif>(GMT+09:30) Australian Central Time (Northern Territory)</option>
                                <option value="Australia/Adelaide" @if (old('role', $user->timezone) == 'Australia/Adelaide')selected="selected"@endif>(GMT+09:30) Australian Central Time (Adelaide)</option>
                                <option value="Australia/Queensland" @if (old('role', $user->timezone) == 'Australia/Queensland')selected="selected"@endif>(GMT+10:00) Australian Eastern Time (Queensland)</option>
                                <option value="Australia/Sydney" @if (old('role', $user->timezone) == 'Australia/Sydney')selected="selected"@endif>(GMT+10:00) Australian Eastern Time (Sydney)</option>
                                <option value="Pacific/Noumea" @if (old('role', $user->timezone) == 'Pacific/Noumea')selected="selected"@endif>(GMT+11:00) Noumea, New Caledonia</option>
                                <option value="Pacific/Norfolk" @if (old('role', $user->timezone) == 'Pacific/Norfolk')selected="selected"@endif>(GMT+11:00) Norfolk Island (Austl.)</option>
                                <option value="Pacific/Tarawa" @if (old('role', $user->timezone) == 'Pacific/Tarawa')selected="selected"@endif>(GMT+12:00) Tarawa</option>
                                <option value="Pacific/Auckland" @if (old('role', $user->timezone) == 'Pacific/Auckland')selected="selected"@endif>(GMT+12:00) New Zealand Time</option>
                            </select>

                            @include('partials/field_error', ['field'=>'timezone'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('time_format') ? ' has-error' : '' }}">
                        <label for="time_format" class="col-sm-2 control-label">{{ __('Time Format') }} (todo)</label>

                        <div class="col-md-6">
     
                            <div class="controls">
                                <label for="12hour" class="radio inline plain"><input type="radio" name="time_format" value="{{ App\User::TIME_FORMAT_12 }}" id="12hour" @if (old('time_format', $user->time_format) == App\User::TIME_FORMAT_12 || !$user->time_format)checked="checked"@endif> {{ __('12-hour clock (e.g. 2:13pm)') }}</label>
                                <label for="24hour" class="radio inline"><input type="radio" name="time_format" value="{{ App\User::TIME_FORMAT_24 }}" id="24hour" @if (old('time_format', $user->time_format) == App\User::TIME_FORMAT_24)checked="checked"@endif> {{ __('24-hour clock (e.g. 14:13)') }}</label>
                            </div>
                            @include('partials/field_error', ['field'=>'time_format'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('enable_kb_shortcuts') ? ' has-error' : '' }}">
                        <label for="enable_kb_shortcuts" class="col-sm-2 control-label">{{ __('Keyboard Shortcuts') }} (todo)</label>

                        <div class="col-md-6">
                            <input type="checkbox" name="enable_kb_shortcuts" @if (old('enable_kb_shortcuts', $user->enable_kb_shortcuts))checked="checked"@endif value="1">
                            @include('partials/field_error', ['field'=>'enable_kb_shortcuts'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('photo_url') ? ' has-error' : '' }}">
                        <label for="photo_url" class="col-sm-2 control-label">{{ __('Photo') }} (todo)</label>

                        <div class="col-md-6">
                            <input type="file" name="photo" id="photo">
                            <p class="help-block">{{ __('Image will be re-sized to 200x200. JPG, GIF, PNG accepted.') }}</p>
                            @include('partials/field_error', ['field'=>'photo_url'])
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save Profile') }}
                            </button>
                        
                            <a href="#" class="btn btn-link">{{ __('Reset password') }} (todo)</a>
                       
                            <a href="#" class="btn btn-link is-error">{{ __('Delete user') }} (todo)</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
