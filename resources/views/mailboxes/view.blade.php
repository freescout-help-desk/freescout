@extends('layouts.app')

@if ($folder->active_count)
    @section('title', '('.(int)$folder->active_count.') '.$folder->getTypeName())
@else
    @section('title', $folder->getTypeName())
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')

    <table class="tickets table table-hover">
        <thead>
        <tr>
            <th class="cb-col" colspan="2"><input type="checkbox" class="toggle-all"></th>
            <th class="customer">
                <a href="#" data-sort="2" data-toggle="tooltip" title="{{ __("Sort") }}">{{ __("Customer") }} <b class="caret"></b></a>
            </th>
            <th class="attachment">&nbsp;</th>
            <th class="subject" colspan="2">
                <a href="#" data-sort="4" data-toggle="tooltip" title="{{ __("Sort") }}">{{ __("Conversation") }} <b class="caret"></b></a>
            </th>
            @if ($folder->type == App\Folder::TYPE_ASSIGNED)
                <th class="owner dropdown" data-toggle="tooltip" title="{{ __("Filter by Assigned To") }}">
                    <a href="#" data-target="#" class="dropdown-toggle" data-toggle="dropdown">{{ __("Assigned To") }} <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                          <li><a class="filter-owner" data-id="1" href="#"><span class="option-title">{{ __("Anyone") }}</span></a></li>
                          <li><a class="filter-owner" data-id="123" href="#"><span class="option-title">{{ __("Me") }}</span></a></li>
                          <li><a class="filter-owner" data-id="123" href="#"><span class="option-title">{{ __("User") }}</span></a></li>
                    </ul>
                </th>
            @endif
            <th class="conv-number">
                <a href="#" data-sort="6" data-toggle="tooltip" title="{{ __("Sort") }}">{{ __("Number") }} <b class="caret"></b></a>
            </th>
            <th class="timestamp">
                <a href="#" data-sort="8">
                    @if ($folder->type == App\Folder::TYPE_CLOSED)
                        {{ __("Closed") }}
                    @elseif ($folder->type == App\Folder::TYPE_DRAFTS)
                        {{ __("Last Updated") }}
                    @elseif ($folder->type == App\Folder::TYPE_DELETED)
                        {{ __("Deleted") }}
                    @else
                        {{ __("Waiting Since") }}
                    @endif
                    <b class="caret"></b></a>
            </th>
          </tr>
        </thead>
    </table>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    mailboxUpdateInit('{{ App\Mailbox::FROM_NAME_CUSTOM }}');
@endsection