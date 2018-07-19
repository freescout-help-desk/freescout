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
                                        <li><a href="#" data-user_id="{{ $user->id }}">{{ $user->getFullName() }}</a></li>
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
                                    <li><a href="#" data-status="{{ $status }}">{{ App\Conversation::getStatusName($status) }}</a></li>
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
                @include('customers/profile_snippet', ['customer' => $conversation->customer])
                <div class="dropdown customer-trigger" data-toggle="tooltip" title="{{ __("Settings") }}">
                    <a href="javascript:void(0)" class="dropdown-toggle glyphicon glyphicon-cog" data-toggle="dropdown" ></a>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li role="presentation"><a href="{{ route('customers.update', ['id' => $conversation->customer->id]) }}" tabindex="-1" role="menuitem">{{ __("Edit Profile") }}</a></li>
                        <li role="presentation"><a href="javascript:alert('todo: implement customer changing');void(0);" tabindex="-1" role="menuitem">{{ __("Change Customer") }}</a></li>
                    </ul>
                </div>
                <div data-toggle="collapse" href="#collapse-conv-prev" class="customer-hist-trigger">
                    <div class="glyphicon glyphicon-list-alt" data-toggle="tooltip" title="{{ __("Previous Conversations") }}"></div>
                </div>
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
            main
        </div>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    mailboxUpdateInit('{{ App\Mailbox::FROM_NAME_CUSTOM }}');
@endsection