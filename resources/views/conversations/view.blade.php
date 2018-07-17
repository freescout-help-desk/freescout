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
                    <span class="conv-reply glyphicon glyphicon-share-alt" data-toggle="tooltip" data-placement="bottom"  title="{{ __("Reply") }}"></span>
                    <span class="conv-add-note glyphicon glyphicon-edit" data-toggle="tooltip" data-placement="bottom"  title="{{ __("Note") }}"></span>
                    <span class="conv-add-tags glyphicon glyphicon-tag" data-toggle="tooltip" data-placement="bottom"  title="{{ __("Note") }}"></span>
                    <span class="conv-run-workflow glyphicon glyphicon-flash" data-toggle="tooltip" data-placement="bottom"  title="{{ __("Run Workflow") }}"></span>
                    <span class="glyphicon glyphicon-option-horizontal" data-toggle="tooltip" title="{{ __("More Actions") }}"></span>
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
                            <button type="button" class="btn btn-default conv-info-icon"><i class="glyphicon glyphicon-{{ App\Conversation::$status_icons[$conversation->status] }}"></i></button>
                            <button type="button" class="btn btn-default dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span>{{ App\Conversation::getStatusName($conversation->status) }}</span> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu conv-status">
                                @foreach (App\Conversation::$statuses as $status => $dummy)
                                    <li><a href="#" data-status="{{ $status }}">{{ App\Conversation::getStatusName($status) }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                </ul>

                <div class="clearfix"></div>

            </div>
            <div id="conv-subject">
                <div>
                    {{ $conversation->subject }}
                </div>
                <div>
                    #<strong>{{ $conversation->number }}</strong>
                    <i class="glyphicon glyphicon-menu-left"></i>
                    <i class="glyphicon glyphicon-menu-right"></i>
                </div>
            </div>
        </div>
        <div id="conv-layout-customer">
            customer
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