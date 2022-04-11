@extends('layouts.app')

@section('title_full', __('Saved Replies').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading">
        {{ __('Saved Replies') }}<a href="{{ route('mailboxes.saved_replies.ajax_html', ['action' => 'create', 'param' => $mailbox->id]) }}" class="btn btn-primary margin-left new-saved-reply" data-trigger="modal" data-modal-size="lg" data-modal-title="{{ __('Create a New Saved Reply') }}" data-modal-no-footer="true" data-modal-on-show="initNewSavedReply">{{ __('New Saved Reply') }}</a>
    </div>
    @if (count($saved_replies))
	    <div class="row-container">
	    	<div class="col-md-11">
				<div class="panel-group accordion margin-top" id="saved-replies-index">
					@include('savedreplies::partials/saved_replies_tree', ['saved_replies' => \SavedReply::listToTree($saved_replies)])
			    </div>
			</div>
		</div>
	@else
		@include('partials/empty', ['icon' => 'comment', 'empty_header' => __("Save time with saved replies!"), 'empty_text' => __('A saved reply is a snippet of text that can be quickly added to the editor when replying to a customer.')])
	@endif
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    initSavedReplies();
@endsection