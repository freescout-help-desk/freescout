@extends('layouts.app')

@section('title_full', __('Settings').' - '.$section_name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="sidebar-title">
        {{ __('Settings') }}
    </div>
    <ul class="sidebar-menu">
        @foreach ($sections as $item_name => $item_info)
            <li @if ($item_name == $section)class="active"@endif><i class="glyphicon glyphicon-{{ $item_info['icon'] }}"></i> <a href="{{ route('settings', ['section' => $item_name]) }}">{{ $item_info['title'] }}</a></li>
        @endforeach
    </ul>
@endsection

@section('content')
    <div class="section-heading">
        {{ $section_name }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container form-container">
        <div class="row">
            <div class="col-xs-12">
                @include(\Eventy::filter('settings.view', 'settings/'.$section, $section))
            </div>
        </div>
    </div>

@endsection
