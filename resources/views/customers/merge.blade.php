@extends('layouts.app')

@section('title_full', $customer->getFullName(true).' - '.__('Customer Profile'))
@section('body_class', 'sidebar-no-height')

@section('body_attrs')@parent data-customer_id="{{ $customer->id }}"@endsection

@section('sidebar')
    <div class="profile-preview">
        @include('customers/profile_menu')
        @include('customers/profile_snippet')
    </div>
@endsection

@section('content')
    @include('customers/profile_tabs', ['extra_tab' => __('Merge')])

    @include('partials/flash_messages')

    <div class="container form-container">
        <div class="row">
            <div class="col-xs-12">
            <form action="" method="POST" class="form-horizontal margin-top">
                {{ csrf_field() }}
                <div class="form-group{{ $errors->has('customer2_id') ? ' has-error' : '' }}">
                    <label class="col-sm-2 control-label">{{ __('Merge With') }}</label>
                    <div class="col-sm-6">
                        <select type="text" name="customer2_id" class="form-control" id="merge_customer2_id" placeholder="{{ __('Search for a customer by name or email') }}…" autocomplete="off" required></select>
                        @include('partials/field_error', ['field'=>'customer2_id'])
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6 col-sm-offset-2">
                        <button type="submit" class="btn btn-primary">{{ __('Merge') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
    @parent
    initMergeCustomers();
@endsection