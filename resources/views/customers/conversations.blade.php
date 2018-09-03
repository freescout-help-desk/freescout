@extends('layouts.app')

@section('title_full', $customer->getFullName().' - '.__('Customer Profile'))
@section('body_class', 'sidebar-no-height')

@section('sidebar')
    <div class="profile-preview">
        @include('customers/profile_snippet')
    </div>
@endsection

@section('content')
    @include('customers/profile_menu')
    @include('partials/flash_messages')

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <br/>
                todo
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    multiInputInit();
@endsection