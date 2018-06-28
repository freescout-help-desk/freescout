@extends('layouts.app')

@section('title_full', __('Mailbox Permissions').'-'.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Permissions') }}
    </div>

    @include('partials/flash_messages')

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <h3> {{ __('Selected Users have access to this mailbox:') }}</h3>
                <p class="help-block">{{ __('Administrators have access to all mailboxes and are not listed here.') }}</p>
            </div>
            <div class="col-xs-12">
                <form class="form-horizontal" method="POST" action="">
                    {{ csrf_field() }}

                    <p><a href="javascript:void(0)" class="selAll">all</a> / <a href="javascript:void(0)" class="selNone">none</a></p>

                    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                        <div class="col-xs-12">
                            <input id="name" type="text" class="form-control input-sized" name="name" value="{{ old('name', $mailbox->name) }}" maxlength="40" required autofocus>

                            @include('partials/field_error', ['field'=>'name'])
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-xs-12">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    mailboxUpdateInit('{{ App\Mailbox::FROM_NAME_CUSTOM }}');
@endsection