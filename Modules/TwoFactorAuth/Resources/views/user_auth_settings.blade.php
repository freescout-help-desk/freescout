@extends('layouts.app')

@section('title_full', __('Authentication').' - '.$user->getFullName())

@section('body_attrs')@parent data-user_id="{{ $user->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Authentication') }}
    </div>

    @include('partials/flash_messages')

    <div class="container form-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="" enctype="multipart/form-data">
                    {{ csrf_field() }}

                    <div class="form-group{{ $errors->has('tfa_enabled') ? ' has-error' : '' }}">
                        <label for="tfa_enabled" class="col-sm-2 control-label">{{ __('Enable Two-Factor Auth') }}</label>

                        <div class="col-sm-6">
                            <div class="controls">
                                <div class="onoffswitch-wrap">
                                    <div class="onoffswitch">
                                        <input type="checkbox" name="tfa_enabled" value="1" id="tfa_enabled" class="onoffswitch-checkbox" @if (old('tfa_enabled', $tfa_enabled))checked="checked"@endif @if (!$tfa_enabled && $not_own_profile) disabled @endif>
                                        <label class="onoffswitch-label" for="tfa_enabled"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($tfa_enabled && !$not_own_profile)
                        <div class="form-group{{ $errors->has('tfa_enabled') ? ' has-error' : '' }}">
                            <label for="tfa_enabled" class="col-sm-2 control-label">{{ __('Recovery Codes') }}</label>

                            <div class="col-sm-6">
                                <label class="control-label">
                                    <a href="{{ route('twofactorauth.ajax_html', ['action' => 'view_codes', 'param' => $user->id]) }}" data-trigger="modal" data-modal-title="{{ __('Recovery Codes') }}" data-modal-size="sm" data-modal-no-footer="true" data-modal-on-show="tfaInitViewCodes">{{ __('View') }}</a>
                                </label>

                                <p class="form-help">
                                    {{ __("If you're not able to use your mobile device, you can use a recovery code to log in. Each code can only be used once.") }}
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="form-group margin-top">
                        <div class="col-md-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save') }}
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
    initUserAuthSettings({{ (int)$tfa_enabled }}, '{{ route('twofactorauth.user_auth_settings_confirm', ['id' => $user->id]) }}');
@endsection