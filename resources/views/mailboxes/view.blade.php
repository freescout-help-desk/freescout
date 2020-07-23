@extends('layouts.app')

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection
@section('body_attrs')@parent data-folder_id="{{ $folder->id }}"@endsection

@if ($folder->active_count)
    @section('title', '('.(int)$folder->getCount().') '.$folder->getTypeName())
@else
    @section('title', $folder->getTypeName())
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')
    @if ($folder->type == App\Folder::TYPE_DELETED)
        <div class="section-heading mailbox-toolbar">
	        <a href="#" class="btn btn-primary mailbox-empty-trash">{{ __('Empty Trash') }}</a>
	    </div>
    @endif
    @include('conversations/conversations_table')
@endsection

@section('javascript')
    @parent
    viewMailboxInit();
@endsection