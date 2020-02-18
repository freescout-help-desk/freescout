@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="container">
    <div class="heading">{{ App\Option::getCompanyName() }} {{ __('Dashboard') }}</div>
    @filter('dashboard.before', '')
    @if (count($mailboxes))
        <div class="dash-cards margin-top">
            @foreach ($mailboxes as $mailbox)
                <div class="dash-card @if (!$mailbox->isActive()) dash-card-inactive @endif">
                    <div class="dash-card-content">
                        <h3 class="text-wrap-break "><a href="{{ route('mailboxes.view', ['id' => $mailbox->id]) }}" class="mailbox-name">@include('mailboxes/partials/mute_icon', ['mailbox' => $mailbox]){{ $mailbox->name }}</a></h3>
                        <div class="dash-card-link text-truncate">
                            <a href="{{ route('mailboxes.view', ['id' => $mailbox->id]) }}" class="text-truncate help-link">{{ $mailbox->email }}</a>
                        </div>
                        <div class="dash-card-list">
                            @php
                                $main_folders = $mailbox->getMainFolders();
                            @endphp
                            @foreach ($main_folders as $folder)
                                @php
                                    $active_count = $folder->getCount($main_folders);
                                @endphp
                                <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$active_count) dash-card-item-empty @endif" title="@if ($active_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $active_count)<span class="waiting-since">/ {{ $folder->getWaitingSince() }}</span>@endif<strong>{{ $active_count }}</strong></a>
                            @endforeach
                        </div>
                        <div class="dash-card-inactive-content">
                            <div class="block-help">
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
                    
                    <div class="dash-card-footer">
                        <div class="btn-group btn-group-justified btn-group-rounded">
                            @if (Auth::user()->can('viewMailboxMenu', Auth::user()))
                                <div class="btn-group dropdown dropup" data-toggle="tooltip" title="{{ __("Mailbox Settings") }}">
                                    <a data-toggle="dropdown" href="#" class="btn btn-trans"><i class="glyphicon glyphicon-cog dropdown-toggle"></i></a>
                                    <ul class="dropdown-menu" role="menu">
                                        @include("mailboxes/settings_menu", ['is_dropdown' => true])
                                    </ul>
                                </div>
                            @endif
                            @if ($mailbox->isActive())
                                <a href="{{ route('conversations.create', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-trans" data-toggle="tooltip" title="{{ __("New Conversation") }}"><i class="glyphicon glyphicon-envelope"></i></a>
                            @endif
                            <a href="{{ route('mailboxes.view', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-trans" data-toggle="tooltip" title="{{ __("Open Mailbox") }}"><i class="glyphicon glyphicon-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif (Auth::user()->isAdmin())
        <a href="{{ route('mailboxes') }}" class="btn btn-primary margin-top">{{ __("Manage Mailboxes") }}</a>
    @else
        @include('partials/empty', ['icon' => 'home', 'empty_text' => __("Welcome home!")])
    @endif
    @filter('dashboard.after', '')
</div>
@endsection
