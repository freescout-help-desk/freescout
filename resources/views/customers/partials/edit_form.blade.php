@include('partials/flash_messages')

<div class="row-container">
    <div class="row">
        <div class="col-xs-12">
            <form class="form-horizontal margin-top" method="POST" action="">
                {{ csrf_field() }}

                <div class="form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
                    <label for="first_name" class="col-sm-2 control-label">{{ __('First Name') }}</label>

                    <div class="col-sm-6">
                        <input id="first_name" type="text" class="form-control input-sized-lg" name="first_name" value="{{ old('first_name', $customer->first_name) }}" maxlength="20">

                        @include('partials/field_error', ['field'=>'first_name'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
                    <label for="last_name" class="col-sm-2 control-label">{{ __('Last Name') }}</label>

                    <div class="col-sm-6">
                        <input id="last_name" type="text" class="form-control input-sized-lg" name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="30">

                        @include('partials/field_error', ['field'=>'last_name'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('job_title') ? ' has-error' : '' }}">
                    <label for="job_title" class="col-sm-2 control-label">{{ __('Job Title') }}</label>

                    <div class="col-sm-6">
                        <input id="job_title" type="text" class="form-control input-sized-lg" name="job_title" value="{{ old('job_title', $customer->job_title) }}" placeholder="{{ __('(optional)') }}" maxlength="100">

                        @include('partials/field_error', ['field'=>'job_title'])
                    </div>
                </div>

                <div class="form-group margin-bottom-0">
                    <label for="emails" class="col-sm-2 control-label">{{ __('Email') }}</label>

                    <div class="col-sm-6">
                        <div class="multi-container">
                            @foreach (old('emails', $emails) as $i => $email)
                                <div class="multi-item {{ $errors->has('emails.'.$i) ? ' has-error' : '' }}">
                                    <div>
                                        <input id="emails" type="text" class="form-control input-sized-lg" name="emails[]" value="{{ $email }}" maxlength="191">
                                        <a href="javascript:void(0)" class="multi-remove" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></a>
                                    </div>

                                    @include('partials/field_error', ['field'=>'emails.'.$i])
                                </div>
                            @endforeach
                            <p class="block-help"><a href="javascript:void(0)" class="multi-add " tabindex="-1">{{ __('Add an email address') }}</a></p>
                        </div>

                        {{-- @include('partials/field_error', ['field'=>'emails.*']) --}}
                    </div>
                </div>

                <div class="form-group{{ $errors->has('websites') ? ' has-error' : '' }}">
                    <label for="websites" class="col-sm-2 control-label">{{ __('Website') }}</label>

                    <div class="col-sm-6">
                        <div class="multi-container">
                            @foreach ($customer->getWebsites(true) as $website)
                                <div class="multi-item">
                                    <div>
                                        <input id="websites" type="text" class="form-control input-sized-lg" name="websites[]" value="{{ $website }}" maxlength="100">
                                        <a href="javascript:void(0)" class="multi-remove" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></a>
                                    </div>
                                </div>
                            @endforeach
                            <p class="block-help"><a href="javascript:void(0)" class="multi-add" tabindex="-1">{{ __('Add a website') }}</a></p>
                        </div>

                        @include('partials/field_error', ['field'=>'websites'])
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-6 col-sm-offset-2">
                        <button type="submit" class="btn btn-primary">
                            @if (!empty($save_button_title))
                                {{ $save_button_title }}
                            @else
                                {{ __('Save Profile') }}
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>