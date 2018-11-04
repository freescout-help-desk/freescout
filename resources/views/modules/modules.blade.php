@extends('layouts.app')

@section('title', __('Modules'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('modules/sidebar_menu')
@endsection

@section('content')
    @if (count($installed_modules))
        <div class="section-heading" id="installed">
            {{ __('Installed Modules') }}
        </div>

        <div class="row-container margin-top">
            @foreach ($installed_modules as $module)
                @include('modules/partials/module_card')
            @endforeach
        </div>
        <div class="clearfix"></div>
    @endif
    @if (count($modules_directory))
        <div class="section-heading" id="directory">
            {{ __('Modules Directory') }}
        </div>

        <div class="row-container margin-top">
            @foreach ($modules_directory as $module)
                @include('modules/partials/module_card')
            @endforeach
        </div>
        <div class="clearfix"></div>
    @endif
@endsection
