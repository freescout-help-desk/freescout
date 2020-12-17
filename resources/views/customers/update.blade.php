@extends('layouts.app')

@section('title_full', $customer->getFullName(true).' - '.__('Customer Profile'))
@section('body_class', 'sidebar-no-height')

@section('body_attrs')@parent data-customer_id="{{ $customer->id }}"@endsection

@section('sidebar')
    <div class="profile-preview">
    	@action('customer.profile_preview_prepend', $customer)
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