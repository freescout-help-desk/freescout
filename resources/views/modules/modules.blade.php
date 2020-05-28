@extends('layouts.app')

@section('title', __('Modules'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('modules/sidebar_menu')
@endsection

@section('content')

    @include('partials/flash_messages')

    @if (count($installed_modules))
        <div class="section-heading" id="installed">
            {{ __('Installed Modules') }}

            <a href="#" data-trigger="modal" data-modal-body="#deactivate_license_modal" data-modal-size="sm" data-modal-no-footer="true" data-modal-title="{{ __('Deactivate License') }}" data-modal-on-show="deactivateLicenseModal" class="small pull-right">{{ __('Deactivate License') }}</a>
        </div>

        @if ($updates_available)
            <div class="row-container margin-top">
                <div class="alert alert-warning">
                    {{ __('There are updates available') }}:
                    <ul>
                        @foreach ($installed_modules as $module)
                            @if (!empty($module['new_version']))
                                <li><a href="#module-{{ $module['alias'] }}">{{ $module['name']}} ({{ $module['new_version'] }})</a></li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="row-container margin-top">
            @foreach ($installed_modules as $module)
                @include('modules/partials/module_card')
            @endforeach
        </div>
        <div class="clearfix"></div>
    @endif
    
    <div class="section-heading" id="directory">
        {{ __('Modules Directory') }}
        @if (!count($installed_modules))
            <a href="#" data-trigger="modal" data-modal-body="#deactivate_license_modal" data-modal-size="sm" data-modal-no-footer="true" data-modal-title="{{ __('Deactivate License') }}" data-modal-on-show="deactivateLicenseModal" class="small pull-right">{{ __('Deactivate License') }}</a>
        @endif
    </div>

    <div class="row-container margin-top">
        <p class="text-help margin-bottom col-xs-12 padding-0">
            {!! __('Want to be notified by email when new modules are released?') !!} <a href="{{ \Config::get('app.freescout_url') }}/subscribe/" target="_blank">{{ __('Subscribe') }}</a>
        </p>
        @foreach ($modules_directory as $module)
            @include('modules/partials/module_card')
        @endforeach
    </div>
    <div class="clearfix margin-bottom"></div>

    <div id="deactivate_license_modal" class="hidden">

        <div class="form-group">
            <select class="form-control deactivate-license-module">
                @foreach ($all_modules as $module_alias => $module_name)
                    <option value="{{ $module_alias }}">{{ App\Module::formatName($module_name) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <input type="text" class="form-control deactivate-license-key" placeholder="{{ __('License Key') }}" />
        </div>

        <div class="margin-top margin-bottom-5">
            <button class="btn btn-primary button-deactivate-license" data-loading-text="{{ __('Deactivate') }}…">{{ __('Deactivate') }}</button>
            <button class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    initModulesList();
@endsection