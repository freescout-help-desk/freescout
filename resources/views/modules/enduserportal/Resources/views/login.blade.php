@extends('enduserportal::layouts.portal')

@section('title', __('Log In'))

@section('content')

    <div class="row margin-top">
        <div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-4 col-lg-offset-4">

            <div class="panel panel-default panel-wizard margin-top">
                
                <div class="panel-body">

                	<div class="wizard-header padding-top-0">
						<h1>{{ __('Log In') }}</h1>
					</div>

					@if (!empty($result) && $result['result'] == 'error' && $result['message'])
						<div class="alert alert-danger">
							{!! $result['message'] !!}
						</div>
					@endif

					@if (!empty($result) && $result['result'] == 'success' && $result['message'])
						<div class="alert alert-success">
							{!! $result['message'] !!}
						</div>
					@else
	                    <form class="margin-top" method="POST" action="">
	                        {{ csrf_field() }}

	                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">

	                            <input id="email" type="email" class="form-control input-md" name="email" value="{{ old('email') }}" placeholder="{{ __('Email Address') }}" required autofocus>

	                            @if ($errors->has('email'))
	                                <span class="help-block">
	                                    <strong>{{ $errors->first('email') }}</strong>
	                                </span>
	                            @endif

	                        </div>

	                        <div class="form-group">
	                          
	                                <button type="submit" class="btn btn-primary btn-block btn-lg">
	                                    {{ __('Login') }}
	                                </button>
	                          
	                        </div>
	                    </form>
	                @endif

                </div>
            </div>
        </div>
    </div>

@endsection