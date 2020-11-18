@include('partials/flash_messages')

<div class="container form-container">
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

                <div class="form-group margin-bottom-0">
                    <label for="emails" class="col-sm-2 control-label">{{ __('Email') }}</label>

                    <div class="col-sm-6">
                        <div class="multi-container">
                            @foreach (old('emails', $emails) as $i => $email)
                                <div class="multi-item {{ $errors->has('emails.'.$i) ? ' has-error' : '' }}">
                                    <div>
                                        <input type="text" class="form-control input-sized-lg" name="emails[]" value="{{ $email }}" maxlength="191">
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

                <div class="form-group{{ $errors->has('websites') ? ' has-error' : '' }} margin-bottom-0">
                    <label for="phones" class="col-sm-2 control-label">{{ __('Phone') }}</label>

                    <div class="col-sm-6">
                        <div class="multi-container">
                            @foreach ($customer->getPhones(true) as $i => $phone)
                                @if (!empty($phone['type']) && isset($phone['value']))
                                    <div class="multi-item">
                                        <div>
                                            {{-- We are keeping it simple and don't show phone type --}}
                                            <input type="hidden" class="form-control input-sized-lg" name="phones[{{ $i }}][type]" value="{{ $phone['type'] }}">
                                            <input type="text" class="form-control input-sized-lg" name="phones[{{ $i }}][value]" value="{{ $phone['value'] }}">
                                            <a href="javascript:void(0)" class="multi-remove" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></a>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            <p class="block-help" data-max-i="{{ $i }}"><a href="javascript:void(0)" class="multi-add" tabindex="-1">{{ __('Add a phone number') }}</a></p>
                        </div>

                        @include('partials/field_error', ['field'=>'phones'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('company') ? ' has-error' : '' }}">
                    <label for="company" class="col-sm-2 control-label">{{ __('Company') }}</label>

                    <div class="col-sm-6">
                        <input id="company" type="text" class="form-control input-sized-lg" name="company" value="{{ old('company', $customer->company) }}" placeholder="{{ __('(optional)') }}" maxlength="255">

                        @include('partials/field_error', ['field'=>'company'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('job_title') ? ' has-error' : '' }}">
                    <label for="job_title" class="col-sm-2 control-label">{{ __('Job Title') }}</label>

                    <div class="col-sm-6">
                        <input id="job_title" type="text" class="form-control input-sized-lg" name="job_title" value="{{ old('job_title', $customer->job_title) }}" placeholder="{{ __('(optional)') }}" maxlength="100">

                        @include('partials/field_error', ['field'=>'job_title'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('websites') ? ' has-error' : '' }} margin-bottom-0">
                    <label for="websites" class="col-sm-2 control-label">{{ __('Website') }}</label>

                    <div class="col-sm-6">
                        <div class="multi-container">
                            @foreach ($customer->getWebsites(true) as $website)
                                <div class="multi-item">
                                    <div>
                                        <input type="text" class="form-control input-sized-lg" name="websites[]" value="{{ $website }}" maxlength="100">
                                        <a href="javascript:void(0)" class="multi-remove" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></a>
                                    </div>
                                </div>
                            @endforeach
                            <p class="block-help"><a href="javascript:void(0)" class="multi-add" tabindex="-1">{{ __('Add a website') }}</a></p>
                        </div>

                        @include('partials/field_error', ['field'=>'websites'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('social') ? ' has-error' : '' }} margin-bottom-0">
                    <label for="social_profiles" class="col-sm-2 control-label">{{ __('Social Profiles') }}</label>

                    <div class="col-sm-6">
                        <div class="multi-container">
                            @foreach ($customer->getSocialProfiles(true) as $i => $social_profile)
                                @if (isset($social_profile['type']) && isset($social_profile['value']))
                                    <div class="multi-item">
                                        <div>
                                            <div class="input-group input-sized-lg">
                                                <select class="form-control" name="social_profiles[{{ $i }}][type]">
                                                    <option value=""></option>
                                                    @foreach (App\Customer::$social_types as $social_type_id => $social_type_code)
                                                        <option value="{{ $social_type_id }}" @if ((int)$social_profile['type'] == $social_type_id) selected @endif>{{ __(App\Customer::$social_type_names[$social_type_id]) }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="input-group-btn" style="width:0px;"></span>
                                                <input type="text" class="form-control" name="social_profiles[{{ $i }}][value]" value="{{ $social_profile['value'] }}">
                                            </div>
                                            <a href="javascript:void(0)" class="multi-remove" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></a>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            <p class="block-help" data-max-i="{{ $i }}"><a href="javascript:void(0)" class="multi-add" tabindex="-1">{{ __('Add a social profile') }}</a></p>
                        </div>

                        @include('partials/field_error', ['field'=>'social_profiles'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('age') ? ' has-error' : '' }}">
                    <label for="age" class="col-sm-2 control-label">{{ __('Age') }}</label>

                    <div class="col-sm-6">
                        <input id="age" type="text" class="form-control input-sized-lg" name="age" value="{{ old('age', $customer->age) }}" placeholder="{{ __('(optional)') }}" maxlength="7">

                        @include('partials/field_error', ['field'=>'age'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('gender') ? ' has-error' : '' }}">
                    <label for="gender" class="col-sm-2 control-label">{{ __('Gender') }}</label>

                    <div class="col-sm-6">
                        <select class="form-control input-sized-lg" name="gender">
                            <option value=""></option>
                            <option value="{{ App\Customer::GENDER_MALE }}" @if (old('gender', $customer->gender) == App\Customer::GENDER_MALE ) selected @endif>{{ __('Male') }}</option>
                            <option value="{{ App\Customer::GENDER_FEMALE }}" @if (old('gender', $customer->gender) == App\Customer::GENDER_FEMALE ) selected @endif>{{ __('Female') }}</option>
                        </select>

                        @include('partials/field_error', ['field'=>'gender'])
                    </div>
                </div>

                <div class="form-group{{ $errors->has('gender') ? ' has-error' : '' }}">
                    <label for="country" class="col-sm-2 control-label">{{ __('Country') }}</label>

                    <div class="col-sm-6">
                        <select class="form-control input-sized-lg" name="country">
                            <option value=""></option>
                            @foreach (App\Customer::$countries as $country_code => $country_name)
                                <option value="{{ $country_code }}" @if (old('country', $customer->country) == $country_code) selected @endif>{{ __($country_name) }}</option>
                            @endforeach
                        </select>

                        <div class="block-help small margin-bottom-0">
                            <a href="javascript:$('#address-collapse').toggleClass('hidden');void(0);">{{ __('Address') }} <span class="caret"></span></a>
                        </div>

                        @include('partials/field_error', ['field'=>'country'])
                    </div>
                </div>

                <div id="address-collapse" class="hidden">

                    <div class="form-group{{ $errors->has('state') ? ' has-error' : '' }}">
                        <label for="state" class="col-sm-2 control-label">{{ __('State') }}</label>

                        <div class="col-sm-6">
                            <input id="state" type="text" class="form-control input-sized-lg" name="state" value="{{ old('state', $customer->state) }}" placeholder="{{ __('(optional)') }}" maxlength="255">

                            @include('partials/field_error', ['field'=>'state'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                        <label for="city" class="col-sm-2 control-label">{{ __('City') }}</label>

                        <div class="col-sm-6">
                            <input id="city" type="text" class="form-control input-sized-lg" name="city" value="{{ old('city', $customer->city) }}" placeholder="{{ __('(optional)') }}" maxlength="255">

                            @include('partials/field_error', ['field'=>'city'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('address') ? ' has-error' : '' }}">
                        <label for="address" class="col-sm-2 control-label">{{ __('Address') }}</label>

                        <div class="col-sm-6">
                            <input id="address" type="text" class="form-control input-sized-lg" name="address" value="{{ old('address', $customer->address) }}" placeholder="{{ __('(optional)') }}" maxlength="255">

                            @include('partials/field_error', ['field'=>'address'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('zip') ? ' has-error' : '' }}">
                        <label for="zip" class="col-sm-2 control-label">{{ __('ZIP') }}</label>

                        <div class="col-sm-6">
                            <input id="zip" type="text" class="form-control input-sized-lg" name="zip" value="{{ old('zip', $customer->zip) }}" placeholder="{{ __('(optional)') }}" maxlength="12">

                            @include('partials/field_error', ['field'=>'zip'])
                        </div>
                    </div>
                </div>

                <div class="form-group{{ $errors->has('background') ? ' has-error' : '' }}">
                    <label for="background" class="col-sm-2 control-label">{{ __('Background') }}</label>

                    <div class="col-sm-6">
                        <textarea id="background" class="form-control input-sized-lg" name="background" rows="2">{{ old('background', $customer->background) }}</textarea>

                        @include('partials/field_error', ['field'=>'background'])
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