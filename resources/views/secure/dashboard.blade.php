@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="container">
    <div class="heading">{{ config('app.name') }} {{ __('Dashboard') }}</div>
    @if (count($mailboxes))
        <div class="dash-cards margin-top">
            @foreach ($mailboxes as $mailbox)
                <div class="dash-card @if (!$mailbox->isActive()) dash-card-inactive @endif">
                    <div class="dash-card-content">
                        <h3 class="text-wrap-break "><a href="{{ route('mailboxes.view', ['id' => $mailbox->id]) }}">{{ $mailbox->name }}</a></h3>
                        <div class="dash-card-link text-truncate">
                            <a href="{{ route('mailboxes.view', ['id' => $mailbox->id]) }}" class="text-truncate help-link">{{ $mailbox->email }}</a>
                        </div>
                        <div class="dash-card-list">
                            @foreach ($mailbox->getMainFolders() as $folder)
                                <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$folder->active_count) dash-card-item-empty @endif" title="{{  __('View conversations') }}">{{ $folder->getTypeName() }}<span>{{ $folder->active_count }}</span></a>
                            @endforeach
                        </div>
                        <div class="dash-card-inactive-content">
                            <div class="help-block">
                                {{ __('Administrator has not configured mailbox connection settings yet.') }}
                            </div>
                            @if (Auth::user()->can('update', $mailbox))
                                @if (!$mailbox->isOutActive())
                                    <a href="{{ route('mailboxes.connection', ['id' => $mailbox->id]) }}" class="btn btn-link">{{ __('Configure') }}</a>
                                @elseif (!$mailbox->isInActive())
                                    <a href="{{ route('mailboxes.connection.incoming', ['id' => $mailbox->id]) }}" class="btn btn-link">{{ __('Configure') }}</a>
                                @endif
                            @endif
                        </div>
                    </div>
                    @if (Auth::user()->can('update', $mailbox))
                        <div class="dash-card-footer">
                            <a href="{{ route('conversations.create', ['mailbox_id' => $mailbox->id]) }}" class="glyphicon glyphicon-envelope" data-toggle="tooltip" title="{{ __("New Conversation") }}"></a>
                            <a href="{{ route('mailboxes.update', ['id' => $mailbox->id]) }}" class="glyphicon glyphicon-cog" data-toggle="tooltip" title="{{ __("Mailbox Settings") }}"></a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        @include('partials/empty', ['icon' => 'home', 'empty_text' => __("Welcome home!")])
    @endif
</div>
@endsection
