@extends('layouts.app')

@section('title_full', __('Workflows').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading">
        <a href="{{ route('mailboxes.workflows', ['id' => $mailbox->id]) }}" class="btn btn-link padding-left-0"><i class="glyphicon glyphicon-arrow-left"></i><a href="https://freescout.net/module/workflows/" target="_blank" class="small link-help pull-right"><i class="glyphicon glyphicon-question-sign"></i> &nbsp;{{ __('Workflows Help') }}</a>{{ __('Workflows') }}
    </div>
   
    <form class="form-horizontal" method="POST" action="">

        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }} margin-top form-inline margin-left margin-right">
            <label for="name" class="control-label">{{ __('Workflow Name') }}</label>&nbsp;

            <input id="name" type="text" class="form-control wf-name" name="name" value="{{ old('name', $workflow->name) }}" maxlength="75" required="required"> 

            @include('partials/field_error', ['field'=>'name'])
        </div>

    	<ul class="nav nav-tabs nav-tabs-main margin-top" role="tablist">
    	    <li class="active"><a href="#tab-settings" aria-controls="tab-settings" role="tab" data-toggle="tab">{{ __('Settings') }}</a></li>
    	    <li id="wf-conditions-tab" @if ($workflow->isManual()) class="hidden" @endif><a href="#tab-conditions" @if ($mode != 'create' && isset($workflow->errors()['conditions'])) class="text-danger" @endif aria-controls="tab-conditions" role="tab" data-toggle="tab">{{ __('Conditions') }}</a></li>
    	    <li><a href="#tab-actions" @if ($mode != 'create' && isset($workflow->errors()['actions'])) class="text-danger" @endif aria-controls="tab-actions" role="tab" data-toggle="tab">{{ __('Actions') }}</a></li>
    	</ul>

    	<div class="container form-container margin-top">
            <div class="row">
                <div class="col-xs-12">
                    {{ csrf_field() }}

                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="tab-settings">

                            @if ($workflow->created_at)
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">{{ __('Date Created') }}</label>

                                    <div class="col-sm-6">
                                        <label class="control-label text-help">
                                            {{ App\User::dateFormat($workflow->created_at) }}
                                        </label>
                                    </div>
                                </div>
                            @endif

                            @if ($workflow->id && $workflow->isAutomatic())
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">{{ __('Applied To') }}</label>

                                    <div class="col-sm-6">
                                        <label class="control-label text-help">
                                            {{ __(':number conversations', ['number' => $workflow->countConversationsApplied()]) }}
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <div class="form-group{{ $errors->has('type') ? ' has-error' : '' }}">
                                <label for="type" class="col-sm-2 control-label">{{ __('Type') }}</label>

                                <div class="col-sm-8">
                                    <select id="type" class="form-control input-sized" name="type" required autofocus>
                                        <option value="{{ Workflow::TYPE_AUTOMATIC }}" @if (old('type', $workflow->type) == Workflow::TYPE_AUTOMATIC)selected="selected"@endif>{{ __('Automatic') }}</option>
                                        <option value="{{ Workflow::TYPE_MANUAL }}" @if (old('type', $workflow->type) == Workflow::TYPE_MANUAL)selected="selected"@endif>{{ __('Manual') }}</option>
                                    </select>

                                    <p class="form-help wf-type wf-type-auto @if (old('type', $workflow->type) != Workflow::TYPE_AUTOMATIC) hidden @endif">
                                        {!! __(':%tag_start%Automatic workflows:%tag_end% are running in the background. They check conversations for matching conditions and carry out specified actions automatically. Automatic workflows run only one time on a conversation.', ['%tag_start%' => '<strong>', '%tag_end%' => '</strong>']) !!} {{ __("If automatic workflow does not contain any date-related conditions, it is executed when a new message is created or some condition's value changes (for example, conversation status changes).") }}
                                    </p>
                                    <p class="form-help wf-type wf-type-manual @if (old('type', $workflow->type) != Workflow::TYPE_MANUAL) hidden @endif">
                                        {!! __("A :%tag_start%Manual workflow:%tag_end% doesn't do anything until you execute it for a conversation. Manual workflows do not have conditions and just perform all their actions when executed.", ['%tag_start%' => '<strong>', '%tag_end%' => '</strong>']) !!}
                                    </p>

                                    @include('partials/field_error', ['field'=>'type'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('apply_to_prev') ? ' has-error' : '' }} wf-type wf-type-auto @if (old('type', $workflow->type) != Workflow::TYPE_AUTOMATIC) hidden @endif">
                                <label for="apply_to_prev" class="col-sm-2 control-label">{{ __('Apply to Previous') }}</label>

                                <div class="col-sm-6">
                                    <div class="controls">
                                        <div class="onoffswitch-wrap">
                                            <div class="onoffswitch">
                                                <input type="checkbox" name="apply_to_prev" value="1" id="apply_to_prev" class="onoffswitch-checkbox" @if (old('apply_to_prev', $workflow->apply_to_prev))checked="checked"@endif >
                                                <label class="onoffswitch-label" for="apply_to_prev"></label>
                                            </div>

                                            <i class="glyphicon glyphicon-info-sign icon-info icon-info-inline" data-toggle="popover" data-trigger="hover" data-html="true" data-content="{{ __('Apply this workflow to all previous conversations matching conditions') }}"></i>
                                        </div>
                                        @if (!$workflow->apply_to_prev)
                                            <div class="hidden form-help" id="apply-to-prev-alert">
                                                <span class="text-warning">{{ __('When this options is enabled the workflow may be applied to a large number of the past conversations and changes can not be undone. It is recommended to backup the application before turning this option on.') }}</span>
                                            </div>
                                        @endif
                                        {{--@if ($mode != 'create' && $workflow->isAutomatic() && !$workflow->apply_to_prev)
                                            <div class="text-warning">
                                                {{ __(':number conversations', ['number' => $workflow->countConversationsToApply()]) }}
                                            </div>
                                        @endif--}}
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div role="tabpanel" class="tab-pane" id="tab-conditions">
                            <div class="col-sm-10 form-inline wf-blocks">
                                @include('workflows::partials/dynamic_block', [
                                    'data' => $workflow->conditions,
                                    'row_config' => Workflow::conditionsConfig($mailbox->id),
                                    'mode' => 'conditions'
                                ])
                            </div>
                            <div class="clearfix"></div>
                        </div>

                        <div role="tabpanel" class="tab-pane" id="tab-actions">
                            <div class="col-sm-10 form-inline wf-blocks">
                                @include('workflows::partials/dynamic_block', [
                                    'data' => $workflow->actions,
                                    'row_config' => Workflow::actionsConfig($mailbox->id),
                                    'mode' => 'actions'
                                ])
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </div>

                    <hr/>

                    <div class="form-group{{ $errors->has('active') ? ' has-error' : '' }}">
                        <label for="active" class="col-sm-2 control-label">{{ __('Active') }}</label>

                        <div class="col-sm-6">
                            <div class="controls">
                                <div class="onoffswitch-wrap">
                                    <div class="onoffswitch">
                                        <input type="checkbox" name="active" value="1" id="active" class="onoffswitch-checkbox" @if (old('active', $workflow->active))checked="checked"@endif >
                                        <label class="onoffswitch-label" for="active"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group margin-top">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary wf-save wf-save-execute hidden">
                                {{ __('Save & Execute') }}
                            </button> 
                            <button type="submit" class="btn btn-primary wf-save wf-save-save">
                                {{ __('Save') }}
                            </button>
                            @if ($mode != 'create')
                                <a href="#" id="wf-delete-trigger" data-loading-text="{{ __('Deleting') }}…" data-wf_id="{{ $workflow->id }}" class="btn btn-link text-danger">{{ __('Delete') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@include('partials/editor')
@include('partials/calendar')

@section('javascript')
    @parent
    initUpdateWorkflow({{ Workflow::TYPE_AUTOMATIC }}, '{{ __('Delete this workflow?') }}');
@endsection