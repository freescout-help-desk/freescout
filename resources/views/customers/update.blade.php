@extends('layouts.app')

@section('title_full', $customer->getFullName().' - '.__('Customer Profile'))

@section('sidebar')
    123
@endsection

@section('content')
    @include('customers/profile_menu')
    @include('partials/flash_messages')

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="">
                    {{ csrf_field() }}

                    <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
                        <label for="first_name" class="col-sm-2 control-label">{{ __('First Name') }}</label>

                        <div class="col-md-6">
                            <input id="first_name" type="text" class="form-control input-sized2" name="first_name" value="{{ old('first_name', $customer->first_name) }}" maxlength="20" required autofocus>

                            @include('partials/field_error', ['field'=>'first_name'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
                        <label for="last_name" class="col-sm-2 control-label">{{ __('Last Name') }}</label>

                        <div class="col-md-6">
                            <input id="last_name" type="text" class="form-control input-sized2" name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="30" required autofocus>

                            @include('partials/field_error', ['field'=>'last_name'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('job_title') ? ' has-error' : '' }}">
                        <label for="job_title" class="col-sm-2 control-label">{{ __('Job Title') }}</label>

                        <div class="col-md-6">
                            <input id="job_title" type="text" class="form-control input-sized2" name="job_title" value="{{ old('job_title', $customer->job_title) }}" placeholder="{{ __('(optional)') }}" maxlength="100">

                            @include('partials/field_error', ['field'=>'job_title'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('emails') ? ' has-error' : '' }}">
                        <label for="emails" class="col-sm-2 control-label">{{ __('Email') }}</label>

                        <div class="col-md-6">
                            <div class="multi-container">
                                @foreach ($emails as $email)
                                    <div class="multi-item">
                                        <input id="emails" type="text" class="form-control input-sized2" name="emails[]" value="{{ $email }}" maxlength="100">
                                        <a href="javascript:void(0)" class="multi-remove" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></a>
                                    </div>
                                @endforeach
                                <p class="help-block"><a href="javascript:void(0)" class="multi-add" tabindex="-1">{{ __('Add an email address') }}</a></p>
                            </div>

                            @include('partials/field_error', ['field'=>'emails'])
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save Profile') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    multiInputInit();
@endsection