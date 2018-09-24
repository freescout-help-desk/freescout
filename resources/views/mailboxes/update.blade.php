@extends('layouts.app')

@section('title_full', __('Edit Mailbox'))

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Edit Mailbox') }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container">
        <div class="row">
            <div class="col-xs-12">
                <form class="form-horizontal margin-top" method="POST" action="">
                    {{ csrf_field() }}

                    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                        <label for="name" class="col-sm-2 control-label">{{ __('Mailbox Name') }}</label>

                        <div class="col-sm-6">
                            <input id="name" type="text" class="form-control input-sized" name="name" value="{{ old('name', $mailbox->name) }}" maxlength="40" required autofocus>

                            @include('partials/field_error', ['field'=>'name'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        <label for="email" class="col-sm-2 control-label">{{ __('Email Address') }}</label>

                        <div class="col-sm-6">
                            <input id="email" type="email" class="form-control input-sized" name="email" value="{{ old('email', $mailbox->email) }}" maxlength="128" required autofocus>
                            @include('partials/field_error', ['field'=>'email'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('aliases') ? ' has-error' : '' }}">
                        <label for="aliases" class="col-sm-2 control-label">{{ __('Aliases') }}</label>

                        <div class="col-sm-6">
                            <div class="flexy">
                                <input id="aliases" type="text" class="form-control input-sized" name="aliases" value="{{ old('aliases', $mailbox->aliases) }}" maxlength="255" required autofocus>

                                <i class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left"  data-content="{{ __('Aliases are other email addresses that also forward to your mailbox address. Separate each email with a comma.') }}"></i>
                            </div>
                            
                            @include('partials/field_error', ['field'=>'aliases'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('from_name') ? ' has-error' : '' }}">
                        <label for="from_name" class="col-sm-2 control-label">{{ __('From Name') }}</label>

                        <div class="col-sm-6">
                            <div class="flexy">
                                <select id="from_name" class="form-control input-sized" name="from_name" required autofocus>
                                    <option value="{{ App\Mailbox::FROM_NAME_MAILBOX }}" @if (old('from_name', $mailbox->from_name) == App\Mailbox::FROM_NAME_MAILBOX)selected="selected"@endif>{{ __('Mailbox Name') }}</option>
                                    <option value="{{ App\Mailbox::FROM_NAME_USER }}" @if (old('from_name', $mailbox->from_name) == App\Mailbox::FROM_NAME_USER)selected="selected"@endif>{{ __("User's Name") }}</option>
                                    <option value="{{ App\Mailbox::FROM_NAME_CUSTOM }}" @if (old('from_name', $mailbox->from_name) == App\Mailbox::FROM_NAME_CUSTOM)selected="selected"@endif>{{ __('Custom Name') }}</option>
                                </select>

                                <i class="glyphicon glyphicon-info-sign icon-info" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="left"  data-content="{{ __('Name that will appear in the <strong>From</strong> field when a customer views your email.') }}"></i>
                            </div>

                            @include('partials/field_error', ['field'=>'from_name'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('from_name_custom') ? ' has-error' : '' }}{{ old('from_name', $mailbox->from_name) != App\Mailbox::FROM_NAME_CUSTOM ? ' hidden' : '' }}" id="from_name_custom_container">
                        <label for="from_name_custom" class="col-sm-2 control-label">{{ __('Custom From Name') }}</label>

                        <div class="col-sm-6">
                            <input id="from_name_custom" type="text" class="form-control input-sized" name="from_name_custom" value="{{ old('from_name_custom', $mailbox->from_name_custom) }}" maxlength="128">
                            @include('partials/field_error', ['field'=>'from_name_custom'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('ticket_status') ? ' has-error' : '' }}">
                        <label for="ticket_status" class="col-sm-2 control-label">{{ __('Status After Replying') }}</label>

                        <div class="col-sm-6">
                            <select id="ticket_status" class="form-control input-sized" name="ticket_status" required autofocus>
                                <option value="{{ App\Mailbox::TICKET_STATUS_ACTIVE }}" @if (old('ticket_status', $mailbox->ticket_status) == App\Mailbox::TICKET_STATUS_ACTIVE)selected="selected"@endif>{{ __('Active') }}</option>
                                <option value="{{ App\Mailbox::TICKET_STATUS_PENDING }}" @if (old('ticket_status', $mailbox->ticket_status) == App\Mailbox::TICKET_STATUS_PENDING)selected="selected"@endif>{{ __('Pending') }}</option>
                                <option value="{{ App\Mailbox::TICKET_STATUS_CLOSED }}" @if (old('ticket_status', $mailbox->ticket_status) == App\Mailbox::TICKET_STATUS_CLOSED)selected="selected"@endif>{{ __('Closed') }}</option>
                            </select>

                            @include('partials/field_error', ['field'=>'ticket_status'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('template') ? ' has-error' : '' }}">
                        <label for="template" class="col-sm-2 control-label">{{ __('Email Template') }} (todo)</label>

                        <div class="col-sm-6">
     
                            <div class="controls">
                                {{-- Afer implementing remove readonly--}}
                                <label for="template_plain" class="radio inline plain"><input type="radio" name="template" value="{{ App\Mailbox::TEMPLATE_PLAIN }}" disabled="disabled" class="disabled" id="template_plain" @if (old('template', $mailbox->template) == App\Mailbox::TEMPLATE_PLAIN || !$mailbox->template)checked="checked"@endif> {{ __('Plain Template') }}</label>
                                <label for="template_fancy" class="radio inline"><input type="radio" name="template" value="{{ App\Mailbox::TEMPLATE_FANCY }}" id="template_fancy" @if (old('template', $mailbox->template) == App\Mailbox::TEMPLATE_FANCY)checked="checked"@endif> {{ __('Fancy Template') }}</label>
                            </div>
                            @include('partials/field_error', ['field'=>'template'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('ticket_assignee') ? ' has-error' : '' }}">
                        <label for="ticket_assignee" class="col-sm-2 control-label">{{ __('Default Assignee') }}</label>

                        <div class="col-sm-6">
                            <select id="ticket_assignee" class="form-control input-sized" name="ticket_assignee" required autofocus>
                                <option value="{{ App\Mailbox::TICKET_ASSIGNEE_ANYONE }}" @if (old('ticket_assignee', $mailbox->ticket_assignee) == App\Mailbox::TICKET_ASSIGNEE_ANYONE)selected="selected"@endif>{{ __('Anyone') }}</option>
                                <option value="{{ App\Mailbox::TICKET_ASSIGNEE_REPLYING_UNASSIGNED }}" @if (old('ticket_assignee', $mailbox->ticket_assignee) == App\Mailbox::TICKET_ASSIGNEE_REPLYING_UNASSIGNED)selected="selected"@endif>{{ __('Person Replying (if Unassigned)') }}</option>
                                <option value="{{ App\Mailbox::TICKET_ASSIGNEE_REPLYING }}" @if (old('ticket_assignee', $mailbox->ticket_assignee) == App\Mailbox::TICKET_ASSIGNEE_REPLYING)selected="selected"@endif>{{ __('Person Replying') }}</option>
                            </select>

                            @include('partials/field_error', ['field'=>'ticket_assignee'])
                        </div>
                    </div>

                    <div class="form-group{{ $errors->has('signature') ? ' has-error' : '' }}">
                        <label for="signature" class="col-sm-2 control-label">{{ __('Email Signature') }}</label>

                        <div class="col-md-9 signature-editor">
                            <textarea id="signature" class="form-control" name="signature" rows="8">{{ old('signature', $mailbox->signature) }}</textarea>
                            @include('partials/field_error', ['field'=>'signature'])
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Save') }}
                            </button>

                            <a href="#" data-trigger="modal" data-modal-body="#delete_mailbox_modal" data-modal-no-footer="true" data-modal-title="{{ __('Delete the :mailbox_name mailbox?', ['mailbox_name' => $mailbox->name]) }}" data-modal-on-show="deleteMailboxModal" class="btn btn-link text-danger">{{ __('Delete mailbox') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="delete_mailbox_modal" class="hidden">
        <div class="text-large">{{ __('Deleting this mailbox will remove all historical data and deactivate related workflows and reports.') }}</div>
        <div class="text-large margin-top margin-bottom-5">{{ __('Please confirm your password:') }}</div>
        <div class="row">
            <div class="col-xs-7">
                <input type="password" class="form-control delete-mailbox-pass" />
            </div>
        </div>
        <div class="margin-top margin-bottom-5">
            <button class="btn btn-danger button-delete-mailbox" data-loading-text="{{ __('Processing') }}…">{{ __('Delete Mailbox') }}</button>
            <button class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
        </div>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    mailboxUpdateInit('{{ App\Mailbox::FROM_NAME_CUSTOM }}');
@endsection