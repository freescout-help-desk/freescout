@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="container">

   <div id="select-dash-filter" class="dropdown">
    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu3" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        View All
    </button>
    <div id="select-dash-filter-menu" class="dropdown-menu" aria-labelledby="dropdownMenu3">
        <button onclick="SetDashFilter(1)" class="dropdown-item select-dash-filter-item" type="button">Focus View</button>
        <button onclick="SetDashFilter(2)" class="dropdown-item select-dash-filter-item active" type="button">View Open</button>
        <button onclick="SetDashFilter(3)" class="dropdown-item select-dash-filter-item" type="button">View All</button>
    </div>
    </div>

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
                                $count_mine = 0;
                            @endphp
                            @foreach ($main_folders as $folder)
                                @php
                                    $active_count = $folder->getCount($main_folders);
                                    $open_count = $folder->getOpenCount($main_folders);
                                @endphp
                                                               
                            
                                @if ($folder->getType() == $folder::TYPE_UNASSIGNED)
                                    <!-- <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong>{{ $open_count }}</strong></a> -->
                                    <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @else dash-card-list-inverse-red action-focus focus-any @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong>{{ $open_count }}</strong></a>
                                    
                                @elseif ($folder->getType() == $folder::TYPE_MINE)
                                    @php 
                                        $count_mine = $open_count;
                                    @endphp

                                    @if (($active_count != 0) && ($active_count != $open_count))
                                        <!-- <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong>{{ $active_count }}<span class="dash-card-list-item-unobtrusive"> / {{ $open_count }}</span></strong></a> -->
                                        <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @else action-focus focus-any @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong class=""><i class="glyphicon glyphicon-time glyph-working" style=""></i>&nbsp;{{ $active_count }} / <span class="dash-card-list-item-waiting">{{ $open_count }}</span></strong></a>
                                    @elseif (($active_count == 0) and ($open_count != 0))
                                        <!-- <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong>{{ $active_count }}<span class="dash-card-list-item-unobtrusive"> / {{ $open_count }}</span></strong></a> -->
                                        <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @else action-focus focus-any @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong class="dash-card-list-item-waiting">{{ $open_count }}</strong></a>
                                    @elseif ($active_count === $open_count)
                                        <!-- <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong>{{ $active_count }}<span class="dash-card-list-item-unobtrusive"> / {{ $open_count }}</span></strong></a> -->
                                        <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @else action-focus focus-any @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong class="all-items-active">{{ $active_count }}<span class=""> / {{ $open_count }}</span></strong></a>
                                    @else
                                        <!-- <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong><span class="dash-card-list-item-unobtrusive">0 / {{ $open_count }}</span></strong></a> -->
                                        <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$open_count) dash-card-item-empty @else action-focus focus-any @endif" title="@if ($open_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $open_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong><span class="dash-card-list-item-waiting">{{ $open_count }}</span></strong></a> 
                                    @endif
                                @elseif ($folder->getType() == $folder::TYPE_STARRED)
                                    <!-- <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$active_count) dash-card-item-empty @endif" title="@if ($active_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $active_count)<span class="waiting-since">/ {{ $folder->getWaitingSince() }}</span>@endif<strong>&#11088;&nbsp;{{ $active_count }}</strong></a> -->
                                    <!-- Icon &#11088; -->
                                    @if ($active_count != 0)
                                        <a class="dash-card-list-item-starred" href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$active_count) dash-card-item-empty @else focus-any @endif" title="@if ($active_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">@if (!$folder->isIndirect() && $active_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong><i class="glyphicon glyphicon-star dropdown-toggle dash-icon-starred"></i>&nbsp;{{ $active_count }}</strong></a>
                                    @endif
                                @elseif ($folder->getType() == $folder::TYPE_ASSIGNED)
                                    @php 
                                        $count_assigned = $open_count - $count_mine;
                                    @endphp
                                    <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$count_assigned) dash-card-item-empty @else focus-any @endif" title="@if ($count_assigned){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $count_assigned)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong>{{ $count_assigned }}</strong></a>
                                @else
                                    <a href="{{ route('mailboxes.view.folder', ['id' => $mailbox->id, 'folder_id' => $folder->id]) }}" class="dash-card-list-item @if (!$active_count) dash-card-item-empty @endif" title="@if ($active_count){{  __('Waiting Since') }}@else{{  __('View conversations') }}@endif">{{ $folder->getTypeName() }}@if (!$folder->isIndirect() && $active_count)<span class="waiting-since">  {{ $folder->getWaitingSince() }}</span>@endif<strong>{{ $active_count }}</strong></a>
                                @endif
                                
                                
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

@section('footer-scripts')
      @include('scripts.dashboard-script')
@endsection

