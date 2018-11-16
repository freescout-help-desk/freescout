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
@endsection

@section('javascript')
    @parent
    initModulesList();
@endsection