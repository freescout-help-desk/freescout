@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="container">
    <div class="heading">{{ config('app.name') }} {{ __('Dashboard') }}</div>
    @if (count($mailboxes))
        <div class="dash-cards margin-top">
            @foreach ($mailboxes as $mailbox)
                <div class="dash-card">
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
                    </div>
                    @if (Auth::user()->can('update', $mailbox))
                        <div class="dash-card-footer">
                            <a href="{{ route('mailboxes.update', ['id' => $mailbox->id]) }}" class="glyphicon glyphicon-cog" data-toggle="tooltip" title="{{ __("Mailbox Settings") }}"></a>
                            <a href="{{ route('mailboxes.update', ['id' => $mailbox->id]) }}" class="glyphicon glyphicon-edit" data-toggle="tooltip" title="{{ __("New Conversation") }}"></a>
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
