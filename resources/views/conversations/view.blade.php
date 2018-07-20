@extends('layouts.app')

@section('title', '#'.$conversation->number.' '.$conversation->subject)
@section('body_class', 'body-conv')

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
                        <div class="btn-group" data-toggle="tooltip" title="{{ __("Assignee") }}">
                            <button type="button" class="btn btn-default conv-info-icon"><i class="glyphicon glyphicon-user"></i></button>
                            <button type="button" class="btn btn-default dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                @if ($conversation->user)
                                    <span>{{ $conversation->user->getFullName() }}</span> 
                                @else
                                    <span>{{ __("Anyone") }}</span> 
                                @endif
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="#" data-user_id="-1">{{ __("Anyone") }}</a></li>
                                <li><a href="#" data-user_id="{{ Auth::user()->id }}">{{ __("Me") }}</a></li>
                                @foreach ($mailbox->users as $user)
                                    @if ($user->id != Auth::user()->id)
                                        <li @if ($conversation->user->id == $user->id) class="active" @endif><a href="#" data-user_id="{{ $user->id }}">{{ $user->getFullName() }}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="btn-group" data-toggle="tooltip" title="{{ __("Status") }}">
                            <button type="button" class="btn btn-{{ App\Conversation::$status_colors[$conversation->status] }} conv-info-icon"><i class="glyphicon glyphicon-{{ App\Conversation::$status_icons[$conversation->status] }}"></i></button>
                            <button type="button" class="btn btn-{{ App\Conversation::$status_colors[$conversation->status] }} dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span>{{ App\Conversation::getStatusName($conversation->status) }}</span> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu conv-status">
                                @foreach (App\Conversation::$statuses as $status => $dummy)
                                    <li @if ($conversation->status == $status) class="active" @endif><a href="#" data-status="{{ $status }}">{{ App\Conversation::getStatusName($status) }}</a></li>
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
                <div class="conv-subjwrap">
                    <div class="conv-subjtext">
                        {{ $conversation->subject }}
                    </div>
                    <div class="conv-numnav">
                        <i class="glyphicon glyphicon-star-empty conv-star" onclick="alert('todo: implement starred conversations')" data-toggle="tooltip" title="{{ __("Star Conversation") }}"></i>&nbsp; # <strong>{{ $conversation->number }}</strong>
                    </div>
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
                    <div class="thread thread-lineitem">
                        <div class="thread-message">
                            <div class="thread-header">
                                <div class="thread-title">
                                </div>
                                <div class="thread-info">
                                    <span class="thread-date"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="thread">
                        <div class="thread-photo">
                            <img src="/img/default-avatar.png" alt="">
                        </div>
                        <div class="thread-message">
                            <div class="thread-header">
                                <div class="thread-title">
                                    <div class="thread-person">
                                        <strong>
                                            @if ($thread->type == App\Thread::TYPE_CUSTOMER)
                                                {{ $thread->customer->getFullName() }}
                                            @else
                                                @if ($thread->user->id == Auth::user()->id)
                                                    {{ __("you") }}
                                                @else
                                                    {{ $thread->user->getFullName() }}
                                                @endif
                                            @endif
                                        </strong> 
                                        @if ($loop->index == 0)
                                            {{ __("started the conversation") }}
                                        @else
                                            {{ __("replied") }}
                                        @endif
                                    </div>
                                    <div class="thread-recipients">
                                        @if ($thread->getTos() == 1 && $thread->getTos()[0] == $mailbox->email)
                                        @elseif ($thread->getTos())
                                            <div>
                                                <strong>
                                                    {{ __("To") }}:
                                                </strong>
                                                {{ implode(', ', $thread->getTos()) }}
                                            </div>
                                        @endif
                                        @if ($thread->getCcs() == 1 && $thread->getCcs()[0] == $mailbox->email)
                                        @elseif ($thread->getCcs())
                                            <div>
                                                <strong>
                                                    {{ __("Cc") }}:
                                                </strong>
                                                {{ implode(', ', $thread->getCcs()) }}
                                            </div>
                                        @endif
                                        @if ($thread->getBccs() == 1 && $thread->getBccs()[0] == $mailbox->email)
                                        @elseif ($thread->getBccs())
                                            <div>
                                                <strong>
                                                    {{ __("Bcc") }}:
                                                </strong>
                                                {{ implode(', ', $thread->getBccs()) }}
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
                                                {{ App\Thread::getStatusName($thread->status) }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="thread-body">
                                {!! $thread->getCleanBody() !!}
                            </div>
                        </div>
                        <div class="dropdown thread-options">
                            <span class="dropdown-toggle glyphicon glyphicon-option-vertical" data-toggle="dropdown"></span>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="#" title="" class="thread-edit-trigger">{{ __("Edit") }}</a></li>
                                <li><a href="javascript:alert('todo: implement hiding threads');void(0);" title="" class="thread-hide-trigger">{{ __("Hide") }}</a></li>
                                <li><a href="javascript:alert('todo: implement new conversation from thread');void(0);" title="{{ __("Start a conversation with this thread") }}" class="new-conv">{{ __("New Conversation") }}</a></li>
                                <li><a href="#" title="{{ __("Show original email") }}" class="thread-orig-trigger">{{ __("Show Original") }}</a></li>
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
    mailboxUpdateInit('{{ App\Mailbox::FROM_NAME_CUSTOM }}');
@endsection