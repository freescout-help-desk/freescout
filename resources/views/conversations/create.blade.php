@extends('layouts.app')

@section('title', __('(no subject)'))
@section('body_class', 'body-conv')
@if (!empty($conversation->id))
    @section('body_attrs')@parent data-conversation_id="{{ $conversation->id }}"@endsection
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')

    @php
        if (empty($thread)) {
            $thread = $conversation->threads()->first();
        }
        if (!$thread) {
            $thread = new App\Thread();
        }
    @endphp

    <div id="conv-layout" class="conv-new">
        <div id="conv-layout-header">
            <div id="conv-toolbar">
                
                <div class="conv-actions">
                    <h2>{{ __("New Conversation") }}</h2>

                    <div class="btn-group">
                        <button type="button" class="btn btn-default active" id="email-conv-switch" onclick="switchToNewEmailConversation({{ App\Conversation::TYPE_EMAIL }})"><i class="glyphicon glyphicon-envelope"></i></button>
                        <button type="button" class="btn btn-default" id="phone-conv-switch" onclick="switchToNewPhoneConversation()"><i class="glyphicon glyphicon-earphone"></i></button>
                    </div>
                </div>

                <div class="conv-info">
                    #@if ($conversation->number)<strong>{{ $conversation->number }}</strong>@else<strong class="conv-new-number">{{ __("Pending") }}@endif</strong>
                </div>

                <div class="clearfix"></div>

            </div>
        </div>
        <div id="conv-layout-customer">
            @action('conversation.new_conv_layout_side', $conversation, $mailbox)
        </div>
        <div id="conv-layout-main" class="conv-new-form">
            <div class="conv-block">
                <div class="row">
                    <div class="col-xs-12">
                        <form class="form-horizontal margin-top form-reply" method="POST" action="" id="form-create">
                            {{ csrf_field() }}
                            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}"/>
                            <input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}"/>
                            {{-- For phone conversation --}}
                            <input type="hidden" name="is_note" value="{{ ($conversation->type == App\Conversation::TYPE_PHONE ? '1' : '') }}"/>
                            <input type="hidden" name="is_phone" value="{{ ($conversation->type == App\Conversation::TYPE_PHONE ? '1' : '') }}"/>
                            <input type="hidden" name="type" value="{{ $conversation->type }}"/>
                            {{-- For drafts --}}
                            <input type="hidden" name="thread_id" value="{{ $thread->id }}"/>
                            {{-- Customer ID is needed not to create empty customers when creating a new phone conversations --}}
                            <input type="hidden" name="customer_id" value="{{ $conversation->customer_id }}"/>
                            <input type="hidden" name="is_create" value="1"/>
                            
                            @if ($conversation->created_by_user_id)
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">{{ __('Author') }}</label>

                                    <div class="col-sm-9">
                                        <label class="control-label text-help">
                                            <i class="glyphicon glyphicon-user"></i> {{ $conversation->created_by_user->getFullName() }}
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <div class="form-group phone-conv-fields{{ $errors->has('name') ? ' has-error' : '' }}">
                                <label for="name" class="col-sm-2 control-label">{{ __('Customer Name') }}</label>

                                <div class="col-sm-9">

                                    <select class="form-control parsley-exclude draft-changer" name="name" id="name" multiple required autofocus/>
                                        @if (!empty($name))
                                            {{-- We use customer ID here because customer may have no emails --}}
                                            @foreach ($name as $name_customer_id => $name_customer_name)
                                                <option value="{{ $name_customer_id }}" selected="selected">{{ $name_customer_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    @include('partials/field_error', ['field'=>'name'])
                                </div>
                            </div>

                            <div class="form-group phone-conv-fields{{ $errors->has('phone') ? ' has-error' : '' }}">
                                <label for="phone" class="col-sm-2 control-label">{{ __('Phone') }}</label>

                                <div class="col-sm-9">

                                    <select class="form-control draft-changer" name="phone" id="phone" placeholder="{{ __('(optional)') }}" multiple/>
                                        @if (!empty($phone))
                                            <option value="{{ $phone }}" selected="selected">{{ $phone }}</option>
                                        @endif
                                    </select>

                                    @include('partials/field_error', ['field'=>'phone'])
                                </div>
                            </div>

                            <div id="conv-to-email-group">
                                <div class="form-group phone-conv-fields{{ $errors->has('to_email') ? ' has-error' : '' }}" id="field-to_email">
                                    <label for="to_email" class="col-sm-2 control-label">{{ __('Email') }}</label>

                                    <div class="col-sm-9">

                                        <select class="form-control draft-changer" name="to_email" id="to_email" placeholder="{{ __('(optional)') }}" multiple/>
                                            @if (!empty($to_email))
                                                @foreach ($to_email as $email => $name)
                                                    <option value="{{ $email }}" selected="selected">{{ $name }}</option>
                                                @endforeach
                                            @endif
                                        </select>

                                        @include('partials/field_error', ['field'=>'to'])
                                    </div>
                                </div>

                                <div class="col-sm-9 col-sm-offset-2 toggle-field phone-conv-fields" id="toggle-email">
                                    <a href="javascript:void(0);">{{ __('Add Email') }}</a>
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('to') ? ' has-error' : '' }}" id="field-to">
                                <label for="to" class="col-sm-2 control-label">{{ __('To') }}</label>

                                <div class="col-sm-9">

                                    <select class="form-control recipient-select" name="to[]" id="to" multiple required autofocus/>
                                        @if ($to)
                                            @foreach ($to as $email => $name)
                                                <option value="{{ $email }}" selected="selected">{{ $name }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    <label class="checkbox @if (count($to) <= 1) hidden @endif" for="multiple_conversations" id="multiple-conversations-wrap">
                                        <input type="checkbox" name="multiple_conversations" value="1" id="multiple_conversations"> {{ __('Send emails separately to each recipient') }}
                                    </label>

                                    @include('partials/field_error', ['field'=>'to'])
                                </div>
                            </div>

                            <div class="form-group email-conv-fields{{ $errors->has('cc') ? ' has-error' : '' }} @if (!$conversation->cc) hidden @endif field-cc">
                                <label for="cc" class="col-sm-2 control-label">{{ __('Cc') }}</label>

                                <div class="col-sm-9">
                                    <select class="form-control recipient-select" name="cc[]" id="cc" multiple/>
                                        @if ($conversation->getCcArray())
                                            @foreach ($conversation->getCcArray() as $cc)
                                                <option value="{{ $cc }}" selected="selected">{{ $cc }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    @include('partials/field_error', ['field'=>'cc'])
                                </div>
                            </div>

                            <div class="form-group email-conv-fields{{ $errors->has('bcc') ? ' has-error' : '' }} @if (!$conversation->bcc) hidden @endif field-cc">
                                <label for="bcc" class="col-sm-2 control-label">{{ __('Bcc') }}</label>

                                <div class="col-sm-9">

                                    <select class="form-control recipient-select" name="bcc[]" id="bcc" multiple/>
                                        @if ($conversation->getBccArray())
                                            @foreach ($conversation->getBccArray() as $bcc)
                                                <option value="{{ $bcc }}" selected="selected">{{ $bcc }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    @include('partials/field_error', ['field'=>'bcc'])
                                </div>
                            </div>

                            <div class="col-sm-9 col-sm-offset-2 email-conv-fields toggle-field @if ($conversation->cc && $conversation->bcc) hidden @endif">
                                <a href="javascript:void(0);" class="help-link" id="toggle-cc">Cc/Bcc</a>
                            </div>

                            <div class="form-group{{ $errors->has('subject') ? ' has-error' : '' }}">
                                <label for="subject" class="col-sm-2 control-label">{{ __('Subject') }}</label>

                                <div class="col-sm-9">
                                    <input id="subject" type="text" class="form-control" name="subject" value="{{ old('subject', $conversation->subject) }}" maxlength="998" required autofocus>@action('conversation.create_form.subject_append')
                                    @include('partials/field_error', ['field'=>'subject'])
                                </div>
                            </div>
                            @action('conversation.create_form.after_subject', $conversation, $mailbox, $thread)

                            <div class="thread-attachments attachments-upload">
                                <ul></ul>
                            </div>

                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }} conv-reply-body">
                                <div class="col-sm-12">
                                    <textarea id="body" class="form-control" name="body" rows="13" data-parsley-required="true" data-parsley-required-message="{{ __('Please enter a message') }}">{{ old('body', $thread->body) }}</textarea>
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
    @include('conversations/editor_bottom_toolbar', ['new_converstion' => true])
    @action('new_conversation_form.after', $conversation)
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    initReplyForm(true, true, true);
    initNewConversation(@if ($conversation->type == App\Conversation::TYPE_PHONE){{ 'true' }}@endif);
@endsection
