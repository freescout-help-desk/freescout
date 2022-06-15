@extends('layouts.app')

@section('title_full', __('Notifications').' - '.$user->first_name.' '.$user->last_name)

@if ($user->id == Auth::user()->id)
    @section('body_attrs')@parent data-own_profile="true" @endsection
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Notifications') }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container">
        <div class="row">
            <div class="col-md-11 col-lg-9">
                <form method="POST" action="" class="user-subscriptions">
                    {{ csrf_field() }}
                    @include('users/subscriptions_table')
                    <div class="form-group margin-top">    
                        <button type="submit" class="btn btn-primary">
                            {{ __('Save Notifications') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    notificationsInit();
@endsection
