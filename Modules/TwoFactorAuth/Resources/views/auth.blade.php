@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">

            @include('auth/banner')

            <div class="panel panel-default panel-shaded">
                
                <div class="panel-body">

                    @action('login_form.before')

                    <form class="form-inline margin-top @if ($error) has-error @endif" method="POST" action="{{ route('login') }}">
                        {{ csrf_field() }}

                        @foreach((array)$credentials as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        @if ($remember)
                            <input type="hidden" name="remember" value="on">
                        @endif

                        <h3 class="text-center">{{ __("Two-Factor Authentication") }}</h3>

                        <p class="text-center">
                            {{ __("To continue open up authenticator app on your mobile device and issue 2FA code or use one of your recovery codes.") }}
                        </p>
                        <div class="text-center">
                           
                            <input type="text" name="{{ $input }}" id="{{ $input }}"
                                   class="@if($error) is-invalid @endif form-control input-lg text-center"
                                   minlength="{{ config('laraguard.totp.digits') }}" maxlength="{{ config('laraguard.totp.digits') }}" placeholder="123456" required>
                            @if ($error)
                                <div class="help-block">
                                    {{ __('The code is invalid or has expired.') }}
                                </div>
                            @endif
                           
                            <div class="margin-top-10 margin-bottom">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Confirm Code') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    @action('login_form.after')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
