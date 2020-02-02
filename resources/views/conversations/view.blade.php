@extends('layouts.app')

@section('title_full', '#'.$conversation->number.' '.$conversation->getSubject().($customer ? ' - '.$customer->getFullName(true) : ''))
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
                    {{-- There should be no spaced between buttons --}}
                    @if (!$conversation->isPhone())
                        <span class="conv-reply conv-action glyphicon glyphicon-share-alt" data-toggle="tooltip" data-placement="bottom" title="{{ __("Reply") }}"></span>
                    @endif
                    <span class="conv-add-note conv-action glyphicon glyphicon-edit" data-toggle="tooltip" data-placement="bottom" title="{{ __("Note") }}"></span>
                    @if ($conversation->state != App\Conversation::STATE_DELETED)
                        <span class="hidden-xs conv-action glyphicon glyphicon-trash conv-delete" data-toggle="tooltip" data-placement="bottom" title="{{ __("Delete") }}"></span>
                    @else
                        <span class="hidden-xs conv-action glyphicon glyphicon-trash conv-delete-forever" data-toggle="tooltip" data-placement="bottom" title="{{ __("Delete Forever") }}"></span>
                    @endif
                    @action('conversation.action_buttons', $conversation, $mailbox){{--<span class="conv-run-workflow conv-action glyphicon glyphicon-flash" data-toggle="tooltip" data-placement="bottom"  title="{{ __("Run Workflow") }}" onclick="alert('todo: implement workflows')" data-toggle="tooltip"></span>--}}

                    <div class="dropdown conv-action" data-toggle="tooltip" title="{{ __("More Actions") }}">
                        <span class="conv-action glyphicon glyphicon-option-horizontal dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"></span>
                        <ul class="dropdown-menu dropdown-with-icons">
                            @action('conversation.prepend_action_buttons', $conversation, $mailbox)
                            <li>
                                <a href="#" class="conv-follow @if ($is_following) hidden @endif" data-follow-action="follow"><i class="glyphicon glyphicon-bell"></i> {{ __("Follow") }}</a>
                                <a href="#" class="conv-follow @if (!$is_following) hidden @endif" data-follow-action="unfollow"><i class="glyphicon glyphicon-bell"></i> {{ __("Unfollow") }}</a>
                            </li>
                            <li><a href="#" class="conv-forward"><i class="glyphicon glyphicon-arrow-right"></i> {{ __("Forward") }}</a></li>
                            @if (Auth::user()->can('move', App\Conversation::class))
                                <li><a href="{{ route('conversations.ajax_html', ['action' =>
                                            'move_conv']) }}?conversation_id={{ $conversation->id }}" data-trigger="modal" data-modal-title="{{ __("Move Conversation") }}" data-modal-no-footer="true" data-modal-on-show="initMoveConv"><i class="glyphicon glyphicon-log-out"></i> {{ __("Move") }}</a></li>
                            @endif
                            @if ($conversation->state != App\Conversation::STATE_DELETED)
                                <li class="hidden-lg hidden-md hidden-sm"><a href="#" class="conv-delete"><i class="glyphicon glyphicon-trash"></i> {{ __("Delete") }}</a></li>
                            @else
                                <li class="hidden-lg hidden-md hidden-sm"><a href="#" class="conv-delete-forever"><i class="glyphicon glyphicon-trash"></i> {{ __("Delete Forever") }}</a></li>
                            @endif
                            @action('conversation.append_action_buttons', $conversation, $mailbox)
                        </ul>
                    </div>
                </div>

                <ul class="conv-info">
                    @if ($conversation->state != App\Conversation::STATE_DELETED)
                        <li>
                            <div class="btn-group" id="conv-assignee" data-toggle="tooltip" title="{{ __("Assignee") }}: {{ $conversation->getAssigneeName(true) }}">
                                <button type="button" class="btn btn-default conv-info-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-user"></i></button>
                                <button type="button" class="btn btn-default dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span>{{ $conversation->getAssigneeName(true) }}</span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu conv-user">
                                    <li @if (!$conversation->user_id) class="active" @endif><a href="#" data-user_id="-1">{{ __("Anyone") }}</a></li>
                                    <li @if ($conversation->user_id == Auth::user()->id) class="active" @endif><a href="#" data-user_id="{{ Auth::user()->id }}">{{ __("Me") }}</a></li>
                                    @foreach ($mailbox->usersHavingAccess(true) as $user)
                                        @if ($user->id != Auth::user()->id)
                                            <li @if ($conversation->user_id == $user->id) class="active" @endif><a href="#" data-user_id="{{ $user->id }}">{{ $user->getFullName() }}</a></li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endif
                    <li>
                        <div class="btn-group" id="conv-status" data-toggle="tooltip" title="{{ __("Status") }}: {{ $conversation->getStatusName() }}">
                            @if ($conversation->state != App\Conversation::STATE_DELETED)
                                <button type="button" class="btn btn-{{ App\Conversation::$status_classes[$conversation->status] }} btn-light conv-info-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-{{ App\Conversation::$status_icons[$conversation->status] }}"></i></button>
                                <button type="button" class="btn btn-{{ App\Conversation::$status_classes[$conversation->status] }} btn-light dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span>{{ $conversation->getStatusName() }}</span> <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu conv-status">
                                    @if ($conversation->status != App\Conversation::STATUS_SPAM)
                                        @foreach (App\Conversation::$statuses as $status => $dummy)
                                            <li @if ($conversation->status == $status) class="active" @endif><a href="#" data-status="{{ $status }}">{{ App\Conversation::statusCodeToName($status) }}</a></li>
                                        @endforeach
                                    @else
                                        <li><a href="#" data-status="not_spam">{{ __('Not Spam') }}</a></li>
                                    @endif
                                </ul>
                            @else
                                <button type="button" class="btn btn-grey btn-light conv-info-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-trash"></i></button>
                                <button type="button" class="btn btn-grey btn-light dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span>{{ __('Deleted') }}</span> <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu conv-status">
                                    <li><a href="#" class="conv-restore-trigger">{{ __('Restore') }}</a></li>
                                </ul>
                            @endif
                        </div>
                    </li><li class="conv-next-prev">
                        <a href="{{ $conversation->urlPrev() }}" class="glyphicon glyphicon-menu-left" data-toggle="tooltip" title="{{ __("Newer") }}"></a>
                        <a href="{{ $conversation->urlNext() }}" class="glyphicon glyphicon-menu-right" data-toggle="tooltip" title="{{ __("Older") }}"></a>
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
                        @action('conversation.after_subject', $conversation, $mailbox)
                        <div class="conv-numnav">
                            <i class="glyphicon conv-star @if ($conversation->isStarredByUser()) glyphicon-star @else glyphicon-star-empty @endif" title="@if ($conversation->isStarredByUser()){{ __("Unstar Conversation") }}@else{{ __("Star Conversation") }}@endif"></i>&nbsp; # <strong>{{ $conversation->number }}</strong>
                        </div>
                        <div id="conv-viewers">
                            @foreach ($viewers as $viewer)
                                <span class="photo-xs viewer-{{ $viewer['user']->id }} @if ($viewer['replying']) viewer-replying @endif" data-toggle="tooltip" title="@if ($viewer['replying']){{ __(':user is replying', ['user' => $viewer['user']->getFullName()]) }}@else{{ __(':user is viewing', ['user' => $viewer['user']->getFullName()]) }}@endif">
                                    @include('partials/person_photo', ['person' => $viewer['user']])
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                @action('conversation.after_subject_block', $conversation, $mailbox)
                <div class="conv-action-wrapper">
                    <div class="conv-block conv-reply-block conv-action-block hidden">
                        <div class="col-xs-12">
                            <form class="form-horizontal form-reply" method="POST" action="">
                                {{ csrf_field() }}
                                <input type="hidden" name="conversation_id" value="{{ $conversation->id }}"/>
                                <input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}"/>
                                <input type="hidden" name="saved_reply_id" value=""/>
                                {{-- For drafts --}}
                                <input type="hidden" name="thread_id" value=""/>
                                <input type="hidden" name="is_note" value=""/>
                                <input type="hidden" name="subtype" value=""/>

                                <div class="form-group{{ $errors->has('to') ? ' has-error' : '' }} conv-recipient conv-recipient-to @if (empty($to_customers)) hidden @endif">
                                    <label for="to" class="control-label">{{ __('To') }}</label>

                                    <div class="conv-reply-field">
                                        @if (!empty($to_customers))
                                            <select name="to" id="to" class="form-control">
                                                @foreach ($to_customers as $to_customer)
                                                    <option value="{{ $to_customer['email'] }}" @if ($to_customer['email'] == $conversation->customer_email)selected="selected"@endif>{{ $to_customer['customer']->getFullName(true) }} &lt;{{ $to_customer['email'] }}&gt;</option>
                                                @endforeach
                                            </select>
                                        @endif
                                        <select class="form-control hidden parsley-exclude draft-changer" name="to_email" id="to_email" multiple required autofocus>
                                        </select>
                                        @include('partials/field_error', ['field'=>'to'])
                                    </div>
                                </div>

                                <div class="form-group{{ $errors->has('cc') ? ' has-error' : '' }} @if (!$cc) hidden @endif field-cc conv-recipient">
                                    <label for="cc" class="control-label">{{ __('Cc') }}</label>

                                    <div class="conv-reply-field">

                                        <select class="form-control recipient-select" name="cc[]" id="cc" multiple>
                                            @if ($cc)
                                                @foreach ($cc as $cc_email)
                                                    <option value="{{ $cc_email }}" selected="selected">{{ $cc_email }}</option>
                                                @endforeach
                                            @endif
                                        </select>

                                        @include('partials/field_error', ['field'=>'cc'])
                                    </div>
                                </div>

                                <div class="form-group{{ $errors->has('bcc') ? ' has-error' : '' }} @if (!$bcc) hidden @endif field-cc conv-recipient">
                                    <label for="bcc" class="control-label">{{ __('Bcc') }}</label>

                                    <div class="conv-reply-field">
                                         <select class="form-control recipient-select" name="bcc[]" id="bcc" multiple>
                                            @if ($bcc)
                                                @foreach ($bcc as $bcc_email)
                                                    <option value="{{ $bcc_email }}" selected="selected">{{ $bcc_email }}</option>
                                                @endforeach
                                            @endif
                                        </select>

                                        @include('partials/field_error', ['field'=>'bcc'])
                                    </div>
                                </div>

                                <div class="form-group cc-toggler @if (empty($to_customers) && !$cc && !$bcc) cc-shifted @endif @if ($cc && $bcc) hidden @endif">
                                    <label class="control-label"></label>
                                    <div class="conv-reply-field">
                                        <a href="javascript:void(0);" class="help-link" id="toggle-cc">Cc/Bcc</a>
                                    </div>
                                </div>

                                @if (!empty($threads[0]) && $threads[0]->type == App\Thread::TYPE_NOTE && $threads[0]->created_by_user_id != Auth::user()->id && $threads[0]->created_by_user)
                                    <div class="alert alert-warning alert-switch-to-note">
                                        <i class="glyphicon glyphicon-exclamation-sign"></i>
                                        {!! __('This reply will go to the customer. :%switch_start%Switch to a note:switch_end if you are replying to :user_name.', ['%switch_start%' => '<a href="javascript:switchToNote();void(0);">', 'switch_end' => '</a>', 'user_name' => $threads[0]->created_by_user->getFullName() ]) !!}
                                    </div>
                                @endif

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
                        @action('reply_form.after', $conversation)
                    </div>
                </div>
            </div>
        </div>

        <div id="conv-layout-customer">
            @if ($customer)
                <div class="conv-customer-header"></div>
                <div class="conv-customer-block conv-sidebar-block">
                    @include('customers/profile_snippet', ['customer' => $customer, 'main_email' => $conversation->customer_email, 'conversation' => $conversation])
                    <div class="dropdown customer-trigger" data-toggle="tooltip" title="{{ __("Settings") }}">
                        <a href="javascript:void(0)" class="dropdown-toggle glyphicon glyphicon-cog" data-toggle="dropdown" ></a>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                            <li role="presentation"><a href="{{ route('customers.update', ['id' => $customer->id]) }}" tabindex="-1" role="menuitem">{{ __("Edit Profile") }}</a></li>
                            <li role="presentation"><a href="{{ route('conversations.ajax_html', ['action' =>
                                            'change_customer']) }}?conversation_id={{ $conversation->id }}" data-trigger="modal" data-modal-title="{{ __("Change Customer") }}" data-modal-no-footer="true" data-modal-on-show="changeCustomerInit" tabindex="-1" role="menuitem">{{ __("Change Customer") }}</a></li>
                            @if (count($prev_conversations))
                                <li role="presentation" class="col3-hidden"><a data-toggle="collapse" href=".collapse-conv-prev" tabindex="-1" role="menuitem">{{ __("Previous Conversations") }}</a></li>
                            @endif
                            {{ \Eventy::action('conversation.customer.menu', $customer, $conversation) }}
                            {{-- No need to use this --}}
                            {{ \Eventy::action('customer_profile.menu', $customer, $conversation) }}
                        </ul>
                    </div>
                    {{--<div data-toggle="collapse" href="#collapse-conv-prev" class="customer-hist-trigger">
                        <div class="glyphicon glyphicon-list-alt" data-toggle="tooltip" title="{{ __("Previous Conversations") }}"></div>
                    </div>--}}
                </div>
                @if (count($prev_conversations))
                    @include('conversations/partials/prev_convs_short')
                @endif
            @endif
            @action('conversation.after_prev_convs', $customer, $conversation, $mailbox)
        </div>
        <div id="conv-layout-main">
            @include('conversations/partials/threads')
        </div>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    initReplyForm();
    initConversation();
@endsection
