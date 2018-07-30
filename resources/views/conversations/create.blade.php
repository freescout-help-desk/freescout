@extends('layouts.app')

@section('title', __('(no subject)'))
@section('body_class', 'body-conv')

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')

    <div id="conv-layout" class="conv-new">
        <div id="conv-layout-header">
            <div id="conv-toolbar">
                
                <div class="conv-actions">
                    <h2>{{ __("New Conversation") }}</h2>

                    <div class="btn-group">
                        <button type="button" class="btn btn-default active"><i class="glyphicon glyphicon-envelope"></i></button>
                        <button type="button" class="btn btn-default" onclick="alert('todo: implement phone conversations');"><i class="glyphicon glyphicon-earphone"></i></button>
                    </div>
                </div>

                <div class="conv-info">
                    #<strong class="conv-new-number">{{ __("Pending") }}</strong>
                </div>

                <div class="clearfix"></div>

            </div>
        </div>
        <div id="conv-layout-customer"></div>
        <div id="conv-layout-main" class="conv-new-form">
            <div class="conv-block">
                <div class="row">
                    <div class="col-xs-12">
                        <form class="form-horizontal margin-top form-reply" method="POST" action="">
                            {{ csrf_field() }}
                            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}"/>
                            <input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}"/>
                            <div class="form-group{{ $errors->has('to') ? ' has-error' : '' }}">
                                <label for="to" class="col-sm-2 control-label">{{ __('To') }}</label>

                                <div class="col-sm-9">
                                    <input id="to" type="text" class="form-control" name="to" value="{{ old('to', $conversation->to) }}" required autofocus>

                                    @include('partials/field_error', ['field'=>'to'])
                                </div>
                            </div>

                            <div class="col-sm-9 col-sm-offset-2 toggle-cc @if ($conversation->cc || $conversation->bcc) hidden @endif">
                                <a href="javascript:void(0);" class="help-link">Cc/Bcc</a>
                            </div>

                            <div class="form-group{{ $errors->has('cc') ? ' has-error' : '' }} @if (!$conversation->cc) hidden @endif field-cc">
                                <label for="cc" class="col-sm-2 control-label">{{ __('Cc') }}</label>

                                <div class="col-sm-9">
                                    <input id="cc" type="text" class="form-control" name="cc" value="{{ old('cc', $conversation->cc) }}">

                                    @include('partials/field_error', ['field'=>'cc'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('bcc') ? ' has-error' : '' }} @if (!$conversation->bcc) hidden @endif field-cc">
                                <label for="bcc" class="col-sm-2 control-label">{{ __('Bcc') }}</label>

                                <div class="col-sm-9">
                                    <input id="bcc" type="text" class="form-control" name="bcc" value="{{ old('bcc', $conversation->bcc) }}">

                                    @include('partials/field_error', ['field'=>'bcc'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('subject') ? ' has-error' : '' }}">
                                <label for="subject" class="col-sm-2 control-label">{{ __('Subject') }}</label>

                                <div class="col-sm-9">
                                    <input id="subject" type="text" class="form-control" name="subject" value="{{ old('subject', $conversation->subject) }}" maxlength="998" required autofocus>

                                    @include('partials/field_error', ['field'=>'subject'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }}">
                                <div class="col-sm-12">
                                    <textarea id="body" class="form-control" name="body" rows="13" data-parsley-required="true" data-parsley-required-message="{{ __('Please enter a message') }}">{{ old('body', $conversation->body) }}</textarea>
                                    <div class="help-block">
                                        @include('partials/field_error', ['field'=>'body'])
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('conversations/editor_bottom_toolbar')
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    newConversationInit();
@endsection