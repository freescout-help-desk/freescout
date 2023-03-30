@extends('layouts.app')

@section('title', __('Add Customer'))

@section('content')
    @include('customers/partials/edit_form', ['save_button_title' => __('Add')])
@endsection

@section('javascript')
    @parent
    multiInputInit();
@endsection