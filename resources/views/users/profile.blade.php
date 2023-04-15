@extends('layouts.app')

@section('title_full', __('Edit User').' - '.$user->getFullName())

@section('body_attrs')@parent data-user_id="{{ $user->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Profile') }}
    </div>

    @include('partials/flash_messages')

    <div class="container form-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="" enctype="multipart/form-data">
                    {{ csrf_field() }}

                    @if ($user->invite_state == App\User::INVITE_STATE_SENT || $user->invite_state == App\User::INVITE_STATE_NOT_INVITED)
                        <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
                            <label for="first_name" class="col-sm-2 control-label">{{ __('Status') }}</label>

                            <div class="col-sm-6">
                                <label class="control-label text-help">
                                    <i class="glyphicon @if ($user->invite_state == App\User::INVITE_STATE_SENT) glyphicon-hourglass @else glyphicon-remove @endif"></i> {{ $user->getInviteStateName() }}
                                </label>
                            </div>
                        </div>
                    @endif

                    @if (auth()->user()->isAdmin())
                        <div class="form-group{{ $errors->has('role') ? ' has-error' : '' }}">
                            <label for="role" class="col-sm-2 control-label">{{ __('Role') }}</label>

                            <div class="col-sm-6">
                                <div class="flexy">
                                    <select id="role" type="text" class="form-control input-sized" name="role" required autofocus>
                                        <option value="{{ App\User::ROLE_USER }}" @if (old('role', $user->role) == App\User::ROLE_USER)selected="selected"@endif>{{ __('User') }}</option>
                                        <option value="{{ App\User::ROLE_ADMIN }}" @if (old('role', $user->role) == App\User::ROLE_ADMIN)selected="selected"@endif>{{ __('Administrator') }}</option>
                                    </select>

                                    <i class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-title="{{ __('Roles') }}" data-content="{{ __('<strong>Administrators</strong> can create new users and have access to all mailboxes and settings') }} <br><br>{{ __('<strong>Users</strong> have access to the mailbox(es) specified in their permissions') }}"></i>
                                </div>

                                @include('partials/field_error', ['field'=>'role'])
                            </div>
                        </div>
                    @endif

                    @if (auth()->user()->isAdmin() && $user->invite_state == App\User::INVITE_STATE_ACTIVATED)
                        <div class="form-group{{ $errors->has('disabled') ? ' has-error' : '' }}">
                            <label for="disabled" class="col-sm-2 control-label">{{ __('Disabled') }}</label>

                            <div class="col-sm-6">
         
                                <div class="controls">
                                    <label for="user_disabled" class="checkbox inline plain"><input type="checkbox" name="disabled" value="{{ App\User::STATUS_DISABLED }}" id="user_disabled" @if (old('disabled', $user->status) == App\User::STATUS_DISABLED)checked="checked"@endif> <span class="text-help">{{ __('Prevent user from logging in') }}</span></label>
                                </div>
                                @include('partials/field_error', ['field'=>'disabled'])
                            </div>
                        </div>
                    @endif

                    @action('user.edit.before_first_name', $user)

                    <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
                        <label for="first_name" class="col-sm-2 control-label">{{ __('First Name') }}</label>

                        <div class="col-sm-6">
                            <input id="first_name" type="text" class="form-control input-sized" name="first_name" value="{{ old('first_name', $user->first_name) }}" maxlength="20" required autofocus>

                            @include('partials/field_error', ['field'=>'first_name'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
                        <label for="last_name" class="col-sm-2 control-label">{{ __('Last Name') }}</label>

                        <div class="col-sm-6">
                            <input id="last_name" type="text" class="form-control input-sized" name="last_name" value="{{ old('last_name', $user->last_name) }}" maxlength="30" required autofocus>

                            @include('partials/field_error', ['field'=>'last_name'])
                        </div>
                    </div>
                    
                    @action('user.edit.before_email', $user)

                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        <label for="email" class="col-sm-2 control-label">{{ __('Email') }}</label>

                        <div class="col-sm-6">
                            <input id="email" type="email" class="form-control input-sized" name="email" value="{{ old('email', $user->email) }}" maxlength="100" required autofocus>

                            @include('partials/field_error', ['field'=>'email'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('emails') ? ' has-error' : '' }}">
                        <label for="emails" class="col-sm-2 control-label">{{ __('Alternate Emails') }}</label>

                        <div class="col-sm-6">
                            <div class="flexy">
                                <input id="emails" type="text" class="form-control input-sized" name="emails" value="{{ old('emails', $user->emails) }}" placeholder="{{ __('(optional)') }}">

                                <i class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-title="{{ __('Alternate Emails') }}" data-content="{{ __("Comma separated list off email addresses from which user can reply to email notifications in addition to user's main Email") }}"></i>
                            </div>

                            @include('partials/field_error', ['field'=>'emails'])
                        </div>
                    </div>

                    @if ($user->id == Auth::user()->id)
                        <div class="form-group">
                            <label for="password" class="col-sm-2 control-label">{{ __('Password') }}</label>

                            <div class="col-sm-6">
                                <label class="control-label"><a href="{{ route('users.password', ['id' => $user->id]) }}">{{ __('Change your password') }}</a></label>
                                
                            </div>
                        </div>
                    @endif

                    <div class="form-group{{ $errors->has('job_title') ? ' has-error' : '' }}">
                        <label for="job_title" class="col-sm-2 control-label">{{ __('Job Title') }}</label>

                        <div class="col-sm-6">
                            <input id="job_title" type="text" class="form-control input-sized" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="{{ __('(optional)') }}" maxlength="100">

                            @include('partials/field_error', ['field'=>'job_title'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('phone') ? ' has-error' : '' }}">
                        <label for="phone" class="col-sm-2 control-label">{{ __('Phone Number') }}</label>

                        <div class="col-sm-6">
                            <input id="phone" type="text" class="form-control input-sized" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="{{ __('(optional)') }}" maxlength="60">

                            @action('user.edit.phone_append', $user)

                            @include('partials/field_error', ['field'=>'phone'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('locale') ? ' has-error' : '' }}">
                        <label for="locale" class="col-sm-2 control-label">{{ __('Language') }}</label>

                        <div class="col-sm-6">
                            <select id="locale" class="form-control input-sized" name="locale">
                                @include('partials/locale_options', ['selected' => old('locale', $user->getLocale())])
                            </select>

                            @include('partials/field_error', ['field'=>'locale'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('timezone') ? ' has-error' : '' }}">
                        <label for="timezone" class="col-sm-2 control-label">{{ __('Timezone') }}</label>

                        <div class="col-sm-6">
                            <select id="timezone" class="form-control input-sized" name="timezone" required autofocus>
                                @include('partials/timezone_options', ['current_timezone' => old('timezone', $user->timezone)])
                            </select>

                            @include('partials/field_error', ['field'=>'timezone'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('time_format') ? ' has-error' : '' }}">
                        <label for="time_format" class="col-sm-2 control-label">{{ __('Time Format') }}</label>

                        <div class="col-sm-6">
     
                            <div class="controls">
                                <label for="12hour" class="radio inline plain"><input type="radio" name="time_format" value="{{ App\User::TIME_FORMAT_12 }}" id="12hour" @if (old('time_format', $user->time_format) == App\User::TIME_FORMAT_12)checked="checked"@endif> {{ __('12-hour clock (e.g. 2:13pm)') }}</label>
                                <label for="24hour" class="radio inline"><input type="radio" name="time_format" value="{{ App\User::TIME_FORMAT_24 }}" id="24hour" @if (old('time_format', $user->time_format) == App\User::TIME_FORMAT_24 || !$user->time_format)checked="checked"@endif> {{ __('24-hour clock (e.g. 14:13)') }}</label>
                            </div>
                            @include('partials/field_error', ['field'=>'time_format'])
                        </div>
                    </div>

                    @action('user.edit.before_photo', $user)

                    <div class="form-group{{ $errors->has('photo_url') ? ' has-error' : '' }}">
                        <label for="photo_url" class="col-sm-2 control-label">{{ __('Photo') }}</label>

                        <div class="col-sm-6">
                            <div class="controls">
                                @if ($user->photo_url)
                                    <div id="user-profile-photo">
                                        <img src="{{ $user->getPhotoUrl() }}" alt="{{ __('Photo') }}" width="50" height="50"><br/>
                                        <a href="#" id="user-photo-delete" data-loading-text="{{ __('Deleting') }}…">{{ __('Delete Photo') }}</a>
                                    </div>
                                @endif

                                <input type="file" name="photo_url">
                                <p class="block-help">{{ __('Image will be re-sized to :dimensions. JPG, GIF, PNG accepted.', ['dimensions' => config('app.user_photo_size').'x'.config('app.user_photo_size')]) }}</p>
                            </div>
                            @include('partials/field_error', ['field'=>'photo_url'])
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save Profile') }}
                            </button>
                            
                            @if (Auth::user()->isAdmin())
                                @if ($user->invite_state == App\User::INVITE_STATE_ACTIVATED)
                                    @if ($user->id != Auth::user()->id)
                                        <a href="#" class="btn btn-link reset-password-trigger" data-loading-text="{{ __('Resetting password') }}…">{{ __('Reset password') }}</a>
                                    @endif
                                @elseif ($user->invite_state == App\User::INVITE_STATE_SENT)
                                    <a href="#" class="btn btn-link resend-invite-trigger" data-loading-text="{{ __('Resending') }}…">{{ __('Re-send invite email') }}</a>
                                @elseif ($user->invite_state == App\User::INVITE_STATE_NOT_INVITED)
                                    <a href="#" class="btn btn-link send-invite-trigger" data-loading-text="{{ __('Sending') }}…">{{ __('Send invite email') }}</a>
                                @endif
                            @endif 
                            
                            @if (Auth::user()->can('delete', $user))
                                <a href="#" id="delete-user-trigger" class="btn btn-link text-danger">{{ __('Delete user') }}</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="delete_user_modal" class="hidden">
        <div>
        <div class="text-center">
            <div class="col-sm-10 col-sm-offset-1 text-large margin-top-10 margin-bottom">{!! __("Deleting :name will deactivate workflows they are tied to and assign their conversations to:", ['name' => '<strong>'.htmlspecialchars($user->getFullName()).'</strong>']) !!}</div>
            <form class="assign_form form-horizontal">
                @foreach (App\Mailbox::all() as $mailbox)
                    <div class="col-sm-9 col-sm-offset-1">
                        <div class="form-group">
                            <label class="col-sm-5 control-label">{{ $mailbox->name }}</label>
                            <div class="col-sm-7">
                                <select name="assign_user[{{ $mailbox->id }}]" class="form-control input-sized">
                                    <option value="-1">{{ __("Anyone") }}</option>
                                    @foreach ($mailbox->usersHavingAccess() as $assign_user)
                                        @if ($assign_user->id != $user->id)
                                            <option value="{{ $assign_user->id }}">{{ $assign_user->getFullName() }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </form>
            <div class="col-sm-12 text-large margin-top">{!! __("If you are sure, type :delete and click the red button.", ['delete' => '<span class="text-danger">DELETE</span>']) !!}</div>
            <div class="col-sm-6 col-sm-offset-3 margin-top-10 margin-bottom">
                <div class="input-group">
                    <input type="text" class="form-control input-delete-user" placeholder="{!! __("Type :delete", ['delete' => '&quot;DELETE&quot;']) !!}">
                    <span class="input-group-btn">
                        <button class="btn btn-danger button-delete-user" disabled="disabled"><i class="glyphicon glyphicon-ok"></i></button>
                    </span>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    userProfileInit();
@endsection
