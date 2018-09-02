@extends('layouts.app')

@section('title_full', __('Change your password').' - '.$user->getFullName())

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Change your password') }}
    </div>

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="">
                    {{ csrf_field() }}

                    <div class="form-group{{ $errors->has('password_current') ? ' has-error' : '' }}">
                        <label for="password_current" class="col-sm-2 control-label">{{ __('Current Password') }}</label>

                        <div class="col-sm-6">
                            <input id="password_current" type="password" class="form-control input-sized" name="password_current" value="{{ old('password_current') }}" required autofocus>

                            @include('partials/field_error', ['field'=>'password_current'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                        <label for="password" class="col-sm-2 control-label">{{ __('New Password') }}</label>

                        <div class="col-sm-6">
                            <input id="password" type="password" class="form-control input-sized" name="password" value="{{ old('password') }}" minlength="8" required autofocus>

                            @include('partials/field_error', ['field'=>'password'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                        <label for="password_confirmation" class="col-sm-2 control-label">{{ __('Confirm Password') }}</label>

                        <div class="col-sm-6">
                            <input id="password_confirmation" type="password" class="form-control input-sized" name="password_confirmation" value="{{ old('password_confirmation') }}" minlength="8" required autofocus>

                            @include('partials/field_error', ['field'=>'password_confirmation'])
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save Password') }}
                            </button>
                            
                            <a href="{{ route('users.profile', ['id' => $user->id]) }}" class="btn btn-link">{{ __('Cancel') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    userProfileInit();
@endsection