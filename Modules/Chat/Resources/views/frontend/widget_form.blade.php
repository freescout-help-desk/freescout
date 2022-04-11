@extends('chat::frontend.layouts.widget_form')

@section('title', __('Chat with us'))

@section('body_attrs')@parent data-mailbox_id_encoded="{{ request()->mailbox_id }}" data-is_widget="1"@endsection

@section('content')
	
	<div id="chatw-form-wrapper">
		@include('chat::frontend/partials/submit_form', ['submit_btn_attrs' => (Request::get('color') ? 'style="background-color: '.Request::get('color').'; border-color: '.Request::get('color').'"': '')])
	</div>
@endsection

@section('chat_javascript')
	@parent
	var chat_default_user_photo = '{{ asset('/img/default-avatar.png') }}';
	var chat_loader_tiny = '{{ asset('/img/loader-tiny.gif') }}';
	var chat_msg_not_delivered = '{{ __('Not delivered') }}';
	var chat_msg_attachment = '{{ __('Attachment') }}';
	var chat_msg_info_trigger = '{{ __('Please update your info') }}';
	var chat_msg_info_title = '{{ __('Edit Your Info') }}';
	var chat_msg_save = '{{ __('Save') }}';
	var chat_msg_optional = '{{ __('(optional)') }}';
	var chat_sound_notification = '{{ asset(\Module::getPublicPath(CHAT_MODULE).'/audio/sound_notification.mp3') }}';
@endsection