@extends('layouts.app')

@section('title_full', '#'.$conversation->number.' '.$conversation->subject.' - '.$conversation->customer->getFullName(true))
@section('body_class', 'body-conv')
@section('body_attrs')@parent data-conversation_id="{{ $conversation->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')

    <div id="conv-layout">
        <div id="conv-layout-header">
            <div id="conv-toolbar">
                
                <div class="conv-actions">
                    <span class="conv-reply conv-action glyphicon glyphicon-share-alt" data-toggle="tooltip" data-placement="bottom" title="{{ __("Reply") }}"></span>
                    <span class="conv-add-note conv-action glyphicon glyphicon-edit" data-toggle="tooltip" data-placement="bottom" title="{{ __("Note") }}" data-toggle="tooltip"></span>
                    <span class="conv-add-tags conv-action glyphicon glyphicon-tag" data-toggle="tooltip" data-placement="bottom" title="{{ __("Tag") }}" onclick="alert('todo: implement tags')"></span>
                    <span class="conv-run-workflow conv-action glyphicon glyphicon-flash" data-toggle="tooltip" data-placement="bottom"  title="{{ __("Run Workflow") }}" onclick="alert('todo: implement workflows')" data-toggle="tooltip"></span>
                    <div class="dropdown conv-action" data-toggle="tooltip" title="{{ __("More Actions") }}">
                        <span class="conv-action glyphicon glyphicon-option-horizontal dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"></span>
                        <ul class="dropdown-menu">
                            <li><a href="#">{{ __("Delete") }} (todo)</a></li>
                            <li><a href="#">{{ __("Follow") }} (todo)</a></li>
                            <li><a href="#">{{ __("Forward") }} (todo)</a></li>
                        </ul>
                    </div>
                </div>

                <ul class="conv-info">
                    <li>
                        <div class="btn-group" data-toggle="tooltip" title="{{ __("Assignee") }}: {{ $conversation->getAssigneeName(true) }}">
                            <button type="button" class="btn btn-default conv-info-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-user"></i></button>
                            <button type="button" class="btn btn-default dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span>{{ $conversation->getAssigneeName(true) }}</span> 
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu conv-user">
                                <li @if (!$conversation->user_id) class="active" @endif><a href="#" data-user_id="-1">{{ __("Anyone") }}</a></li>
                                <li @if ($conversation->user_id == Auth::user()->id) class="active" @endif><a href="#" data-user_id="{{ Auth::user()->id }}">{{ __("Me") }}</a></li>
                                @foreach ($mailbox->usersHavingAccess() as $user)
                                    @if ($user->id != Auth::user()->id)
                                        <li @if ($conversation->user_id == $user->id) class="active" @endif><a href="#" data-user_id="{{ $user->id }}">{{ $user->getFullName() }}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="btn-group" data-toggle="tooltip" title="{{ __("Status") }}: {{ $conversation->getStatusName() }}">
                            <button type="button" class="btn btn-{{ App\Conversation::$status_classes[$conversation->status] }} btn-light conv-info-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-{{ App\Conversation::$status_icons[$conversation->status] }}"></i></button>
                            <button type="button" class="btn btn-{{ App\Conversation::$status_classes[$conversation->status] }} btn-light dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span>{{ $conversation->getStatusName() }}</span> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu conv-status">
                                @foreach (App\Conversation::$statuses as $status => $dummy)
                                    <li @if ($conversation->status == $status) class="active" @endif><a href="#" data-status="{{ $status }}">{{ App\Conversation::statusCodeToName($status) }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </li><li class="conv-next-prev">
                        <i class="glyphicon glyphicon-menu-left" data-toggle="tooltip" title="{{ __("Newer") }}" onclick="alert('todo: implement conversation navigation')"></i>
                        <i class="glyphicon glyphicon-menu-right" data-toggle="tooltip" title="{{ __("Older") }}" onclick="alert('todo: implement conversation navigation')"></i>
                    </li>
                </ul>

                <div class="clearfix"></div>

            </div>
            <div id="conv-subject">
                <div class="conv-subj-block">
                    <div class="conv-subjwrap">
                        <div class="conv-subjtext">
                            {{ $conversation->subject }}
                        </div>
                        <div class="conv-numnav">
                            <i class="glyphicon glyphicon-star-empty conv-star" onclick="alert('todo: implement starred conversations')" data-toggle="tooltip" title="{{ __("Star Conversation") }}"></i>&nbsp; # <strong>{{ $conversation->number }}</strong>
                        </div>
                    </div>
                </div>

                <div class="conv-block conv-reply-block conv-action-block hidden">
                    <div class="col-xs-12">
                        <form class="form-horizontal form-reply" method="POST" action="">
                            {{ csrf_field() }}
                            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}"/>
                            <input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}"/>
                            <input type="hidden" name="is_note" value=""/>

                            <div class="form-group{{ $errors->has('cc') ? ' has-error' : '' }} cc-container">
                                <label for="cc" class="control-label">{{ __('Cc') }}</label>

                                <div class="conv-reply-field">
                                    <input id="cc" type="text" class="form-control" name="cc" value="{{ old('cc', implode(',', $conversation->getCcArray())) }}">
                                    @include('partials/field_error', ['field'=>'cc'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('bcc') ? ' has-error' : '' }} bcc-container">
                                <label for="bcc" class="control-label">{{ __('Bcc') }}</label>

                                <div class="conv-reply-field">
                                    <input id="bcc" type="text" class="form-control" name="bcc" value="{{ old('bcc', implode(',', $conversation->getBccArray())) }}">

                                    @include('partials/field_error', ['field'=>'bcc'])
                                </div>
                            </div>

                            <div class="thread-attachments attachments-upload form-group">
                                <ul></ul>
                            </div>

                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }} conv-reply-body">
                               
                                    <textarea id="body" class="form-control" name="body" rows="13" data-parsley-required="true" data-parsley-required-message="{{ __('Please enter a message') }}">{{ old('body', $conversation->body) }}</textarea>
                                    <div class="help-block has-error">
                                        @include('partials/field_error', ['field'=>'body'])
                                    </div>
                            </div>

                        </form>
                    </div>
                    <div class="clearfix"></div>
                    @include('conversations/editor_bottom_toolbar')
                </div>
            </div>
        </div>
        <div id="conv-layout-customer">
            <div class="conv-customer-block conv-sidebar-block">
                @include('customers/profile_snippet', ['customer' => $customer])
                <div class="dropdown customer-trigger" data-toggle="tooltip" title="{{ __("Settings") }}">
                    <a href="javascript:void(0)" class="dropdown-toggle glyphicon glyphicon-cog" data-toggle="dropdown" ></a>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li role="presentation"><a href="{{ route('customers.update', ['id' => $customer->id]) }}" tabindex="-1" role="menuitem">{{ __("Edit Profile") }}</a></li>
                        <li role="presentation"><a href="javascript:alert('todo: implement customer changing');void(0);" tabindex="-1" role="menuitem">{{ __("Change Customer") }}</a></li>
                        <li role="presentation" class="customer-hist-trigger"><a data-toggle="collapse" href="#collapse-conv-prev" tabindex="-1" role="menuitem">{{ __("Previous Conversations") }}</a></li>
                    </ul>
                </div>
                {{--<div data-toggle="collapse" href="#collapse-conv-prev" class="customer-hist-trigger">
                    <div class="glyphicon glyphicon-list-alt" data-toggle="tooltip" title="{{ __("Previous Conversations") }}"></div>
                </div>--}}
            </div>
            <div class="conv-customer-hist conv-sidebar-block">
                <div class="panel-group accordion accordion-empty">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" href="#collapse-conv-prev">{{ __("Previous Conversations") }} 
                                    <b class="caret"></b>
                                </a>
                            </h4>
                        </div>
                        <div id="collapse-conv-prev" class="panel-collapse collapse {{-- in --}}">
                            <div class="panel-body">
                                <p>todo</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="conv-layout-main">
            @foreach ($threads as $thread)
                
                @if ($thread->type == App\Thread::TYPE_LINEITEM)
                    <div class="thread thread-type-{{ $thread->getTypeName() }}">
                        <div class="thread-message">
                            <div class="thread-header">
                                <div class="thread-title">
                                    @include('conversations/thread_by') 
                                    @if ($thread->action_type == App\Thread::ACTION_TYPE_STATUS_CHANGED)
                                        {{ __("marked as") }} {{ $thread->getStatusName() }}
                                    @elseif ($thread->action_type == App\Thread::ACTION_TYPE_USER_CHANGED)
                                         {{ __("assigned to") }} {{ $thread->getAssignedName() }}
                                    @endif
                                </div>
                                <div class="thread-info">
                                    <span class="thread-date">{{ App\User::dateDiffForHumans($thread->created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown thread-options">
                            <span class="dropdown-toggle glyphicon glyphicon-option-vertical" data-toggle="dropdown"></span>
                            @if (Auth::user()->isAdmin())
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li><a href="{{ route('conversations.ajax_html', ['action' => 
                                        'send_log']) }}?thread_id={{ $thread->id }}" title="{{ __("View outgoing emails") }}" data-trigger="modal" data-modal-title="{{ __("Outgoing Emails") }}">{{ __("Outgoing Emails") }}</a></li>
                                </ul>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="thread thread-type-{{ $thread->getTypeName() }}">
                        <div class="thread-photo">
                            <img src="/img/default-avatar.png" alt="">
                        </div>
                        <div class="thread-message">
                            <div class="thread-header">
                                <div class="thread-title">
                                    <div class="thread-person">
                                        <strong>
                                            @if ($thread->type == App\Thread::TYPE_CUSTOMER)
                                                {{ $thread->customer->getFullName(true) }}
                                            @else
                                                @include('conversations/thread_by')
                                            @endif
                                        </strong> 
                                        @if ($loop->last)
                                            {{ __("started the conversation") }}
                                        @elseif ($thread->type == App\Thread::TYPE_NOTE)
                                            {{ __("added a note") }}
                                        @else
                                            {{ __("replied") }}
                                        @endif
                                    </div>
                                    <div class="thread-recipients">
                                        @if ($thread->getToArray() == 1 && $thread->getToArray()[0] == $mailbox->email)
                                        @elseif ($thread->getToArray())
                                            <div>
                                                <strong>
                                                    {{ __("To") }}:
                                                </strong>
                                                {{ implode(', ', $thread->getToArray()) }}
                                            </div>
                                        @endif
                                        @if ($thread->getCcArray() == 1 && $thread->getCcArray()[0] == $mailbox->email)
                                        @elseif ($thread->getCcArray())
                                            <div>
                                                <strong>
                                                    {{ __("Cc") }}:
                                                </strong>
                                                {{ implode(', ', $thread->getCcArray()) }}
                                            </div>
                                        @endif
                                        @if ($thread->getBccArray() == 1 && $thread->getBccArray()[0] == $mailbox->email)
                                        @elseif ($thread->getBccArray())
                                            <div>
                                                <strong>
                                                    {{ __("Bcc") }}:
                                                </strong>
                                                {{ implode(', ', $thread->getBccArray()) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="thread-info">
                                    <span class="thread-date">{{ App\User::dateDiffForHumans($thread->created_at) }}</span><br/>
                                    @if (in_array($thread->type, [App\Thread::TYPE_CUSTOMER, App\Thread::TYPE_MESSAGE]))
                                        <span class="thread-status">
                                            @if ($loop->index == 0 || $thread->status != App\Thread::STATUS_NOCHANGE)
                                                @php
                                                    $show_status = true;
                                                @endphp
                                            @endif
                                            @if ($loop->index == 0 || $thread->user_id != $threads[$loop->index-1]->user_id)
                                                @if ($thread->user)
                                                    {{ $thread->user->getFullName() }}@if (!empty($show_status)),@endif
                                                @else
                                                    {{ __("Anyone") }}@if (!empty($show_status)),@endif
                                                @endif
                                            @endif
                                            @if (!empty($show_status))
                                                {{ $thread->getStatusName() }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="thread-body">
                                {!! $thread->getCleanBody() !!}
                            </div>
                            @if ($thread->has_attachments)
                                <div class="thread-attachments">
                                    <i class="glyphicon glyphicon-paperclip"></i>
                                    <ul>
                                        @foreach ($thread->attachments as $attachment)
                                            <li>
                                                <a href="{{ $attachment->url() }}" class="break-words" target="_blank">{{ $attachment->file_name }}</a>
                                                <span class="text-help">({{ $attachment->getSizeName() }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                        <div class="dropdown thread-options">
                            <span class="dropdown-toggle glyphicon glyphicon-option-vertical" data-toggle="dropdown"></span>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="#" title="" class="thread-edit-trigger">{{ __("Edit") }} (todo)</a></li>
                                <li><a href="javascript:alert('todo: implement hiding threads');void(0);" title="" class="thread-hide-trigger">{{ __("Hide") }} (todo)</a></li>
                                <li><a href="javascript:alert('todo: implement creating new conversation from thread');void(0);" title="{{ __("Start a conversation from this thread") }}" class="new-conv">{{ __("New Conversation") }}</a></li>
                                <li><a href="{{ route('conversations.ajax_html', ['action' => 
                                        'show_original']) }}?thread_id={{ $thread->id }}" title="{{ __("Show original message headers") }}" data-trigger="modal" data-modal-title="{{ __("Original Message Headers") }}" data-modal-fit="true">{{ __("Show Original") }}</a></li>
                                @if (Auth::user()->isAdmin())
                                    <li><a href="{{ route('conversations.ajax_html', ['action' => 
                                        'send_log']) }}?thread_id={{ $thread->id }}" title="{{ __("View outgoing emails") }}" data-trigger="modal" data-modal-title="{{ __("Outgoing Emails") }}">{{ __("Outgoing Emails") }}</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    conversationInit();
    newConversationInit();
@endsection