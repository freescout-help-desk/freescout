@extends('layouts.app')

@section('title', __('Chats').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('body_class', 'chat-mode')

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')    
<div class="container">
    @include('partials/empty', ['empty_text' => __('There are no conversations here')])
</div>
@endsection