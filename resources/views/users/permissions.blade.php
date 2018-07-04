@extends('layouts.app')

@section('title_full', __('User Permissions').' - '.$user->first_name.' '.$user->last_name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Permissions') }}
    </div>

    @include('partials/flash_messages')

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <h3> {{ __(':first_name has access to the selected mailboxes:', ['first_name' => $user->first_name]) }}</h3>
            </div>
            <div class="col-xs-12">
                <form method="POST" action="">
                    {{ csrf_field() }}

                    <p><a href="javascript:void(0)" class="sel-all">{{ __('all') }}</a> / <a href="javascript:void(0)" class="sel-none">{{ __('none') }}</a></p>

                    <fieldset id="permissions-fields">
                        @foreach ($mailboxes as $mailbox)
                            <div class="control-group">
                                <div class="controls">
                                    <label class="control-label checkbox" for="mailbox-{{ $mailbox->id }}">
                                        <input type="checkbox" name="mailboxes[]" id="mailbox-{{ $mailbox->id }}" value="{{ $mailbox->id }}" @if ($user_mailboxes->contains($mailbox)) checked="checked" @endif> {{ $mailbox->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </fieldset>
                    <div class="form-group margin-top">
                        
                        <button type="submit" class="btn btn-primary">
                            {{ __('Save Permissions') }}
                        </button>
                    
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    permissionsInit();
@endsection