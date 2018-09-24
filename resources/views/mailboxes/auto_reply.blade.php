@extends('layouts.app')

@section('title_full', __('Auto Reply').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading">
        {{ __('Auto Reply') }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="">
                    {{ csrf_field() }}

                    <div class="form-group{{ $errors->has('auto_reply_enabled') ? ' has-error' : '' }}">
                        <label for="auto_reply_enabled" class="col-sm-2 control-label">{{ __('Enable Auto Reply') }}</label>

                        <div class="col-sm-6">
                            <div class="controls">
                                <div class="onoffswitch-wrap">
                                    <div class="onoffswitch">
                                        <input type="checkbox" name="auto_reply_enabled" value="{{ App\Mailbox::TEMPLATE_FANCY }}" id="auto_reply_enabled" class="onoffswitch-checkbox" @if (old('auto_reply_enabled', $mailbox->auto_reply_enabled))checked="checked"@endif >
                                        <label class="onoffswitch-label" for="auto_reply_enabled"></label>
                                    </div>

                                    <i class="glyphicon glyphicon-info-sign icon-info icon-info-inline" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left" data-title="{{ __('Auto Reply') }}" data-content="{{ __('When a customer emails this mailbox, application can send an auto reply to the customer immediately.<br/><br/>One auto-reply is sent per conversation and a customer won\'t receive more than one in a 24-hour period.') }}"></i>
                                </div>
                            </div>
                            @include('partials/field_error', ['field'=>'auto_reply_enabled'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('office_hours') ? ' has-error' : '' }}">
                        <label for="office_hours" class="col-sm-2 control-label">{{ __('Office Hours') }}</label>

                        <div class="col-sm-6">
                            <div class="controls">
                                <label for="auto_reply_enabled" class="control-label text-help">
                                    {{ __('Not yet activated') }}
                                </label>
                            </div>
                            @include('partials/field_error', ['field'=>'office_hours'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('auto_reply_subject') ? ' has-error' : '' }}">
                        <label for="auto_reply_subject" class="col-sm-2 control-label">{{ __('Subject') }}</label>

                        <div class="col-sm-6">
                            <input id="auto_reply_subject" type="text" class="form-control input-sized" name="auto_reply_subject" value="{{ old('auto_reply_subject', $mailbox->auto_reply_subject) }}" maxlength="128" required autofocus>

                            @include('partials/field_error', ['field'=>'auto_reply_subject'])
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="auto_reply_message" class="col-sm-2 control-label">{{ __('Message') }}</label>

                        <div class="col-sm-9 auto_reply_message-editor">
                            <textarea id="auto_reply_message" class="form-control" name="auto_reply_message" rows="8">{{ old('auto_reply_message', $mailbox->auto_reply_message) }}</textarea>
                            <p class="block-help">
                                {{ __("Auto replies don't include your mailbox signature, so be sure to add your contact information if necessary.") }}
                            </p>
                            <div class="{{ $errors->has('auto_reply_message') ? ' has-error' : '' }}">
                                @include('partials/field_error', ['field'=>'auto_reply_message'])
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@include('partials/editor')

@section('javascript')
    @parent
    summernoteInit('#auto_reply_message');
@endsection