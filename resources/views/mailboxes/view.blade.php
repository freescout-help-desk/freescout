@extends('layouts.app')

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection
@section('body_attrs')@parent data-folder_id="{{ $folder->id }}"@endsection

@if ($folder->active_count)
    @section('title', '('.(int)$folder->getCount().') '.$folder->getTypeName().' - '.$mailbox->name)
@else
    @section('title', $folder->getTypeName().' - '.$mailbox->name)
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    <div class="alerts">
        @php
            $flashes = \Helper::maybeShowSendingProblemsAlert();
        @endphp
        @include('partials/flash_messages')
    </div>
    @if ($folder->type == App\Folder::TYPE_DELETED && $folder->total_count)
        <div class="section-heading mailbox-toolbar">
	        <a href="#" class="btn btn-primary mailbox-empty-folder">{{ __('Empty Trash') }}</a>
	    </div>
    @endif
    @if ($folder->type == App\Folder::TYPE_SPAM && $folder->total_count)
        <div class="section-heading mailbox-toolbar">
            <a href="#" class="btn btn-primary mailbox-empty-folder">{{ __('Delete All') }}</a>
        </div>
    @endif
    @include('conversations/conversations_table')
@endsection

@section('javascript')
    @parent
    viewMailboxInit();
@endsection