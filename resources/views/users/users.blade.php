@extends('layouts.app')

@section('title', __('Manage Users'))

@section('content')
<div class="container">
    <div class="flexy-container">
        <div class="flexy-item">
            <span class="heading">{{ __('Users') }}</span>
        </div>
        <div class="flexy-item margin-left">
            <a href="{{ route('users.create') }}" class="btn btn-primary">{{ __('New User') }}</a>
        </div>
        <div class="flexy-block"></div>
    </div>

    <div class="card-list margin-top">
        @foreach ($users as $user)
            <a href="{{ route('users.profile', ['id'=>$user->id]) }}" class="card">
                @if ($user->photo_url)
                    <img src="{{ $user->photo_url }}" />
                @else
                    <i class="card-avatar" data-initial="{{ strtoupper($user->first_name[0]) }}{{ strtoupper($user->last_name[0]) }}"></i>
                @endif
                <h4>{{ $user->first_name }} {{ $user->last_name }}</h4>
                <p>{{ $user->getRoleName(true) }}</p>
            </a>
        @endforeach
    </div>

</div>
@endsection
