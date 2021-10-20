@extends('layouts.app')

@section('title', __('Manage Users'))

@section('content')
<div class="container">
    <div class="flexy-container">
        <div class="flexy-item">
            <span class="heading">{{ __('Users') }}@if (count($users)) <small>({{ count($users) }})</small>@endif</span>
        </div>
        <div class="flexy-item margin-left">
            <a href="{{ route('users.create') }}" class="btn btn-bordered">{{ __('New User') }}</a>
        </div>
        <div class="flexy-block"></div>
        @if (count($users) > 1)
            <div class="flexy-item">
                <div class="input-group">
                    <input type="text" class="form-control" id="search-users" placeholder="{{ __('Search Users') }}...">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button" id="search-users-clear"><i class="glyphicon glyphicon-search"></i></button>
                    </span>
                </div>
            </div>
        @endif
    </div>

    <div id="users-list" class="card-list margin-top">
        @foreach ($users as $user)
            <a href="{{ route('users.profile', ['id'=>$user->id]) }}" class="card hover-shade @if ($user->invite_state != App\User::INVITE_STATE_ACTIVATED) card-inactive @endif">
                @if ($user->isAdmin())
                    <i class="user-admin-badge glyphicon glyphicon-bookmark" data-toggle="tooltip" title="{{ $user->getRoleName(true) }}"></i>
                @endif
                @if ($user->photo_url)
                    <img src="{{ $user->getPhotoUrl() }}" />
                @else
                    <i class="card-avatar" data-initial="{{ strtoupper($user->first_name[0]) }}{{ strtoupper($user->last_name[0] ?? '') }}"></i>
                @endif
                <h4 class="user-q">{{ $user->first_name }} {{ $user->last_name }}</h4>
                <p class="text-truncate user-q">@filter('users.email', $user->email)</p>
                @if ($user->invite_state == App\User::INVITE_STATE_SENT || $user->invite_state == App\User::INVITE_STATE_NOT_INVITED)
                    <i class="invite-state glyphicon @if ($user->invite_state == App\User::INVITE_STATE_SENT) glyphicon-hourglass invited @else glyphicon-remove not-invited @endif" data-toggle="tooltip" data-placement="bottom" title="{{ $user->getInviteStateName() }}"></i>
                @endif
                @if ($user->invite_state == App\User::INVITE_STATE_ACTIVATED && $user->isDisabled())
                    <i class="invite-state not-invited glyphicon glyphicon-ban-circle" data-toggle="tooltip" data-placement="bottom" title="{{ __('Disabled') }}"></i>
                @endif
            </a>
        @endforeach
    </div>

</div>
@endsection

@section('javascript')
    @parent
    initUsers();
@endsection