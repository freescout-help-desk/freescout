@extends('layouts.app')

@section('title', __('Modules'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('modules/sidebar_menu')
@endsection

@section('content')

<div class="section-heading">
    {{ __('Installed Modules') }}
</div>

<div class="row-container">

    

</div>

@if (count($available))
    <div class="section-heading">
        {{ __('Available Modules') }}
    </div>

    <div class="row-container">
        @foreach ($available as $module)
            @include('modules/partials/module_card')
        @endforeach
    </div>
@endif
@endsection
