@extends('layouts.app')

@section('title', __('User Setup Wizard'))

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">

                @include('auth/banner')

                <div class="panel panel-default panel-shaded">
                
                    <div class="panel-body">

                    @if (!$user)
                        <div class="wizard-header wizard-header-small">
                            <h1><i class="glyphicon glyphicon-info-sign text-warning-light"></i> {{ __('User Setup Problem') }}</h1>
                            <p>
                               {{ __('No invite was found. Please contact your administrator to have a new invite email sent.') }}
                            </p>
                        </div>
                        <div class="wizard-footer"></div>
                    @else
                        <div class="wizard-header wizard-header-small">
                            <h1>{{ __('Welcome to :company_name, :first_name!', ['company_name' => App\Option::getCompanyName(), 'first_name' => $user->first_name]) }}</h1>
                            <p>
                               {{ __("Let's setup your profile.") }}
                            </p>
                        </div>
                        <form class="form-horizontal margin-top" method="POST" action="" enctype="multipart/form-data">
                            {{ csrf_field() }}

                            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                <label for="email" class="col-sm-4 control-label">{{ __('Your Email') }}</label>

                                <div class="col-sm-7">
                                    <input id="email" type="email" class="form-control input-sized" name="email" value="{{ old('email', $user->email) }}" maxlength="100" required autofocus>

                                    @include('partials/field_error', ['field'=>'email'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                                <label for="password" class="col-sm-4 control-label">{{ __('Create a Password') }}</label>

                                <div class="col-sm-7">
                                    <input id="password" type="password" class="form-control input-sized" name="password" value="{{ old('password') }}" minlength="8" required autofocus>
                                    <div class="text-help">
                                        {{ __('Your password must be at least 8 characters') }}
                                    </div>

                                    @include('partials/field_error', ['field'=>'password'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                                <label for="password_confirmation" class="col-sm-4 control-label">{{ __('Confirm Password') }}</label>

                                <div class="col-sm-7">
                                    <input id="password_confirmation" type="password" class="form-control input-sized" name="password_confirmation" value="{{ old('password_confirmation') }}" minlength="8" required autofocus>

                                    @include('partials/field_error', ['field'=>'password_confirmation'])
                                </div>
                            </div>

                            @action('user.setup.before_job_title', $user)

                            <div class="form-group{{ $errors->has('job_title') ? ' has-error' : '' }}">
                                <label for="job_title" class="col-sm-4 control-label">{{ __('Job Title') }}</label>

                                <div class="col-sm-7">
                                    <input id="job_title" type="text" class="form-control input-sized" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="{{ __('(optional)') }}" maxlength="100">

                                    @include('partials/field_error', ['field'=>'job_title'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('phone') ? ' has-error' : '' }}">
                                <label for="phone" class="col-sm-4 control-label">{{ __('Phone Number') }}</label>

                                <div class="col-sm-7">
                                    <input id="phone" type="text" class="form-control input-sized" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="{{ __('(optional)') }}" maxlength="60">

                                    @include('partials/field_error', ['field'=>'phone'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('timezone') ? ' has-error' : '' }}">
                                <label for="timezone" class="col-sm-4 control-label">{{ __('Timezone') }}</label>

                                <div class="col-sm-7">
                                    <select id="timezone" class="form-control input-sized" name="timezone" required autofocus>
                                        @include('partials/timezone_options', ['current_timezone' => old('timezone', $user->timezone)])
                                    </select>

                                    @include('partials/field_error', ['field'=>'timezone'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('time_format') ? ' has-error' : '' }}">
                                <label for="time_format" class="col-sm-4 control-label">{{ __('Time Format') }}</label>

                                <div class="col-sm-7">
             
                                    <div class="controls">
                                        <label for="12hour" class="radio inline plain"><input type="radio" name="time_format" value="{{ App\User::TIME_FORMAT_12 }}" id="12hour" @if (old('time_format', $user->time_format) == App\User::TIME_FORMAT_12)checked="checked"@endif> {{ __('12-hour clock (e.g. 2:13pm)') }}</label>
                                        <label for="24hour" class="radio inline"><input type="radio" name="time_format" value="{{ App\User::TIME_FORMAT_24 }}" id="24hour" @if (old('time_format', $user->time_format) == App\User::TIME_FORMAT_24 || !$user->time_format)checked="checked"@endif> {{ __('24-hour clock (e.g. 14:13)') }}</label>
                                    </div>
                                    @include('partials/field_error', ['field'=>'time_format'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('photo_url') ? ' has-error' : '' }}">
                                <label for="photo_url" class="col-sm-4 control-label">{{ __('Photo') }}</label>

                                <div class="col-sm-7">
                                    <div class="controls">
                                        @if ($user->photo_url)
                                            <div id="user-profile-photo">
                                                <img src="{{ $user->getPhotoUrl() }}" alt="{{ __('Profile Image') }}" width="50" height="50"><br/>
                                                <a href="#" id="user-photo-delete" data-loading-text="{{ __('Deleting') }}…">{{ __('Delete Photo') }}</a>
                                            </div>
                                        @endif
                                        <input type="file" name="photo_url">
                                        <p class="block-help">{{ __('Only visible in :app_name.', ['app_name' => \Config::get('app.name')]) }} {{ __('Image will be re-sized to 200x200. JPG, GIF, PNG accepted.') }}</p>
                                    </div>
                                    @include('partials/field_error', ['field'=>'photo_url'])
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-6 col-sm-offset-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Save Profile') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    userProfileInit();
@endsection
