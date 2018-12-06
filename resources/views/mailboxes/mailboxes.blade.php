@extends('layouts.app')

@section('title', __('Manage Mailboxes'))

@section('content')
<div class="container">
    <div class="flexy-container">
        <div class="flexy-item">
            <span class="heading">{{ __('Mailboxes') }}</span>
        </div>
        <div class="flexy-item margin-left">
            <a href="{{ route('mailboxes.create') }}" class="btn btn-bordered">{{ __('New Mailbox') }}</a>
        </div>
        <div class="flexy-block"></div>
    </div>

    <div class="card-list margin-top">
        @foreach ($mailboxes as $mailbox)
            <a href="{{ route('mailboxes.update', ['id'=>$mailbox->id]) }}" class="card no-img hover-shade @if ($mailbox->isActive()) card-active @else card-inactive @endif">
                <h4>{{ $mailbox->name }}</h4>
                <p>{{ $mailbox->email }}</p>
            </a>
        @endforeach
    </div>

</div>
@endsection
