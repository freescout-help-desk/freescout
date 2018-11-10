@extends('layouts.app')

@section('title', __('Saved Replies').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Saved Replies') }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container margin-top">
        <div class="row">
            <div class="col-xs-12">
                <div class="panel-group" id="accordion-saved-replies">
                    <div class="panel panel-info">
                        <div class="panel-heading" id="heading-new-reply">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" data-parent="#accordion-saved-replies" href="#collapse-new-reply">{{ __('New Saved Reply') }}</a>
                            </h4>
                        </div>

                        <div id="collapse-new-reply" class="panel-collapse {{ $is_new ? '' : 'collapse' }}">
                            <div class="panel-body">
                                @include('saved_replies/partials/editor')
                            </div>
                        </div>
                    </div>
                    @foreach ($replies as $reply)
                        <div class="panel panel-default">
                            <div class="panel-heading" id="heading-reply-{{ $reply->id }}">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion-saved-replies" href="#collapse-reply-{{ $reply->id }}">{{ $reply->name }}</a>
                                </h4>
                            </div>

                            <div id="collapse-reply-{{ $reply->id }}" class="panel-collapse {{ $reply_id == $reply->id ? '' : 'collapse' }}">
                                <div class="panel-body">
                                    @include('saved_replies/partials/editor')
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div id="delete_saved_reply_modal" class="hidden">
        <div class="text-center">
            <div class="text-larger margin-top-10">{{ __('Delete saved Reply?') }}</div>
            <div class="form-group margin-top">
                <button class="btn btn-primary delete-saved-reply-ok">{{ __('Delete') }}</button>
                <button class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
            </div>    
        </div>
    </div>

@endsection

@include('partials/editor')

@section('javascript')
    @parent
    savedRepliesInit();
@endsection