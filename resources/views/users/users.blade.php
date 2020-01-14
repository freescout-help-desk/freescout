@extends('layouts.app')

@section('title', __('Manage Users'))

@section('content')
<div class="container">
    <div class="flexy-container">
        <div class="flexy-item">
            <span class="heading">{{ __('Users') }}</span>
        </div>
        <div class="flexy-item margin-left">
            <a href="{{ route('users.create') }}" class="btn btn-bordered">{{ __('New User') }}</a>
        </div>
        <div class="flexy-block"></div>
    </div>

    <div class="card-list margin-top">
        @foreach ($users as $user)
            <a href="{{ route('users.profile', ['id'=>$user->id]) }}" class="card hover-shade @if ($user->invite_state != App\User::INVITE_STATE_ACTIVATED) card-inactive @endif">
                @if ($user->photo_url)
                    <img src="{{ $user->getPhotoUrl() }}" />
                @else
                    <i class="card-avatar" data-initial="{{ strtoupper($user->first_name[0]) }}{{ strtoupper($user->last_name[0] ?? '') }}"></i>
                @endif
                <h4>{{ $user->first_name }} {{ $user->last_name }}</h4>
                <p>{{ $user->getRoleName(true) }}</p>
                @if ($user->invite_state == App\User::INVITE_STATE_SENT || $user->invite_state == App\User::INVITE_STATE_NOT_INVITED)
                    <i class="invite-state glyphicon @if ($user->invite_state == App\User::INVITE_STATE_SENT) glyphicon-hourglass invited @else glyphicon-remove not-invited @endif" data-toggle="tooltip" data-placement="bottom" title="{{ $user->getInviteStateName() }}"></i>
                @endif
            </a>
        @endforeach
    </div>

</div>
@endsection
