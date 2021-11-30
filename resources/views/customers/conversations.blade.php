@extends('layouts.app')

@section('title_full', $customer->getFullName().' - '.__('Customer Profile'))
@section('body_class', 'sidebar-no-height')

@section('body_attrs')@parent data-customer_id="{{ $customer->id }}"@endsection

@section('sidebar')
    <div class="profile-preview">
    	@include('customers/profile_menu')
        @include('customers/profile_snippet')
    </div>
@endsection

@section('content')
    @include('customers/profile_tabs')
    @include('conversations/conversations_table', ['no_checkboxes' => true, 'no_customer' => true, 'conversations_filter' => ['customer_id' => $customer->id] ])
@endsection

@section('javascript')
    @parent
    conversationPagination();
@endsection