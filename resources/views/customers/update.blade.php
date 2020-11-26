@extends('layouts.app')

@section('title_full', $customer->getFullName(true).' - '.__('Customer Profile'))
@section('body_class', 'sidebar-no-height')

@section('sidebar')
    <div class="profile-preview">
        @include('customers/profile_snippet')
    </div>
@endsection

@section('content')
    @include('customers/profile_menu')
    @include('customers/partials/edit_form')
@endsection

@section('javascript')
    @parent
    multiInputInit();
@endsection