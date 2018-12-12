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
                    <span class="conv-reply conv-action glyphicon glyphicon-share-alt" data-toggle="tooltip" data-placement="bottom" title="{{ __("Reply") }}"></span><span class="conv-add-note conv-action glyphicon glyphicon-edit" data-toggle="tooltip" data-placement="bottom" title="{{ __("Note") }}" data-toggle="tooltip"></span>@action('conversation.action_buttons', $conversation, $mailbox){{--<span class="conv-run-workflow conv-action glyphicon glyphicon-flash" data-toggle="tooltip" data-placement="bottom"  title="{{ __("Run Workflow") }}" onclick="alert('todo: implement workflows')" data-toggle="tooltip"></span>--}}<div class="dropdown conv-action" data-toggle="tooltip" title="{{ __("More Actions") }}">
                        <span class="conv-action glyphicon glyphicon-option-horizontal dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"></span>
                        <ul class="dropdown-menu">
                            <li><a href="#" class="conv-delete">{{ __("Delete") }}</a></li>
                            {{--<li><a href="#">{{ __("Follow") }} (todo)</a></li>--}}
                            <li><a href="#">{{ __("Forward") }} (todo)</a></li>
                            @action('conversation.extra_action_buttons', $conversation, $mailbox)
                        </ul>
                    </div>
                </div>

                <ul class="conv-info">
                    @if ($conversation->state != App\Conversation::STATE_DELETED)
                        <li>
                            <div class="btn-group conv-assignee" data-toggle="tooltip" title="{{ __("Assignee") }}: {{ $conversation->getAssigneeName(true) }}">
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
                        <div class="btn-group" data-toggle="tooltip" title="{{ __("Status") }}: {{ $conversation->getStatusName() }}">
                            @if ($conversation->state != App\Conversation::STATE_DELETED)
                                <button type="button" class="btn btn-{{ App\Conversation::$status_classes[$conversation->status] }} btn-light conv-info-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-{{ App\Conversation::$status_icons[$conversation->status] }}"></i></button>
                                <button type="button" class="btn btn-{{ App\Conversation::$status_classes[$conversation->status] }} btn-light dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span>{{ $conversation->getStatusName() }}</span> <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu conv-status">
                                    @foreach (App\Conversation::$statuses as $status => $dummy)
                                        <li @if ($conversation->status == $status) class="active" @endif><a href="#" data-status="{{ $status }}">{{ App\Conversation::statusCodeToName($status) }}</a></li>
                                    @endforeach
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
                    </div>
                </div>

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

                            @if (!empty($to_customers))
                                <div class="form-group{{ $errors->has('to') ? ' has-error' : '' }} conv-recipient">
                                    <label for="to" class="control-label">{{ __('To') }}</label>

                                    <div class="conv-reply-field">
                                        <select name="to" class="form-control">
                                            @foreach ($to_customers as $to_customer)
                                                <option value="{{ $to_customer['email'] }}" @if ($to_customer['email'] == $conversation->customer_email)selected="selected"@endif>{{ $to_customer['customer']->getFullName(true) }} &lt;{{ $to_customer['email'] }}&gt;</option>
                                            @endforeach
                                        </select>
                                        @include('partials/field_error', ['field'=>'to'])
                                    </div>
                                </div>
                            @endif

                            <div class="form-group{{ $errors->has('cc') ? ' has-error' : '' }} conv-recipient">
                                <label for="cc" class="control-label">{{ __('Cc') }}</label>

                                <div class="conv-reply-field">
                                    <input id="cc" type="text" class="form-control" name="cc" value="{{ old('cc', implode(',', $conversation->getCcArray(array_merge($mailbox->getEmails(), [$conversation->customer_email])))) }}">
                                    @include('partials/field_error', ['field'=>'cc'])
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('bcc') ? ' has-error' : '' }} conv-recipient">
                                <label for="bcc" class="control-label">{{ __('Bcc') }}</label>

                                <div class="conv-reply-field">
                                    <input id="bcc" type="text" class="form-control" name="bcc" value="{{ old('bcc', implode(',', $conversation->getBccArray(array_merge($mailbox->getEmails(), [$conversation->customer_email])))) }}">

                                    @include('partials/field_error', ['field'=>'bcc'])
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

        <div id="conv-layout-customer">
            @if ($customer)
                <div class="conv-customer-header"></div>
                <div class="conv-customer-block conv-sidebar-block">
                    @include('customers/profile_snippet', ['customer' => $customer, 'main_email' => $conversation->customer_email])
                    <div class="dropdown customer-trigger" data-toggle="tooltip" title="{{ __("Settings") }}">
                        <a href="javascript:void(0)" class="dropdown-toggle glyphicon glyphicon-cog" data-toggle="dropdown" ></a>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                            <li role="presentation"><a href="{{ route('customers.update', ['id' => $customer->id]) }}" tabindex="-1" role="menuitem">{{ __("Edit Profile") }}</a></li>
                            <li role="presentation"><a href="{{ route('conversations.ajax_html', ['action' => 
                                            'change_customer']) }}?conversation_id={{ $conversation->id }}" data-trigger="modal" data-modal-title="{{ __("Change Customer") }}" data-modal-no-footer="true" data-modal-on-show="changeCustomerInit" tabindex="-1" role="menuitem">{{ __("Change Customer") }}</a></li>
                            @if (count($prev_conversations))
                                <li role="presentation" class="customer-hist-trigger"><a data-toggle="collapse" href=".collapse-conv-prev" tabindex="-1" role="menuitem">{{ __("Previous Conversations") }}</a></li>
                            @endif
                        </ul>
                    </div>
                    {{--<div data-toggle="collapse" href="#collapse-conv-prev" class="customer-hist-trigger">
                        <div class="glyphicon glyphicon-list-alt" data-toggle="tooltip" title="{{ __("Previous Conversations") }}"></div>
                    </div>--}}
                </div>
                @if (count($prev_conversations))
                    {{-- 
                        In mobile view previous conversations must be always hidden.
                        So the only way to achieve this is to have two blocks.
                    --}}
                    @include('conversations/partials/prev_convs_short', ['in' => true])
                    @include('conversations/partials/prev_convs_short', ['mobile' => true])
                @endif
            @endif
        </div>
        <div id="conv-layout-main">
            @foreach ($threads as $thread_index => $thread)
                
                @if ($thread->type == App\Thread::TYPE_LINEITEM)
                    <div class="thread thread-type-{{ $thread->getTypeName() }} thread-state-{{ $thread->getStateName() }}" id="thread-{{ $thread->id }}">
                        <div class="thread-message">
                            <div class="thread-header">
                                <div class="thread-title">
                                    @include('conversations/thread_by') 
                                    @if ($thread->action_type == App\Thread::ACTION_TYPE_STATUS_CHANGED)
                                        {{ __("marked as :status_name", ['status_name' => $thread->getStatusName()]) }}
                                    @elseif ($thread->action_type == App\Thread::ACTION_TYPE_USER_CHANGED)
                                         {{ __("assigned to :assignee", ['assignee' => $thread->getAssigneeName()]) }}
                                    @elseif ($thread->action_type == App\Thread::ACTION_TYPE_CUSTOMER_CHANGED)
                                         {!! __("changed the customer to :customer", ['customer' => '<a href="'.$thread->customer->url().'" title="'.$thread->action_data.'" class="link-black">'.htmlspecialchars($thread->customer->getFullName(true)).'</a>']) !!}
                                    @elseif ($thread->action_type == App\Thread::ACTION_TYPE_DELETED_TICKET)
                                         {{ __("deleted") }}
                                     @elseif ($thread->action_type == App\Thread::ACTION_TYPE_RESTORE_TICKET)
                                          {{ __("restored") }}
                                    @endif
                                </div>
                                <div class="thread-info">
                                    <span class="thread-date" data-toggle="tooltip" title='{{ App\User::dateFormat($thread->created_at) }}'>{{ App\User::dateDiffForHumans($thread->created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown thread-options">
                            <span class="dropdown-toggle glyphicon glyphicon-option-vertical" data-toggle="dropdown"></span>
                            @if (Auth::user()->isAdmin())
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li><a href="{{ route('conversations.ajax_html', ['action' => 
                                        'send_log']) }}?thread_id={{ $thread->id }}" title="{{ __("View outgoing emails") }}" data-trigger="modal" data-modal-title="{{ __("Outgoing Emails") }}" data-modal-size="lg">{{ __("Outgoing Emails") }}</a></li>
                                </ul>
                            @endif
                        </div>
                    </div>
                @elseif ($thread->type == App\Thread::TYPE_MESSAGE && $thread->state == App\Thread::STATE_DRAFT)
                    <div class="thread thread-type-draft" id="thread-{{ $thread->id }}" data-thread_id="{{ $thread->id }}">
                        <div class="thread-message">
                            <div class="thread-header">
                                <div class="thread-title">
                                    <div class="thread-person">
                                        {{--{!! $thread->getActionDescription($conversation->number) !!}--}}
                                        <strong>@include('conversations/thread_by')</strong>
                                        &nbsp;
                                        [{{ __('Draft') }}]
                                    </div>
                                    <div class="btn-group btn-group-xs draft-actions">
                                        <a class="btn btn-default edit-draft-trigger" href="javascript:void(0);">{{ __('Edit') }}</a>
                                        <a class="btn btn-default discard-draft-trigger" href="javascript:void(0)">{{ __('Discard') }}</a>
                                    </div>
                                </div>
                                <div class="thread-info">
                                    {{--<span class="thread-type">[{{ __('Draft') }}] <span>·</span> </span>--}}
                                    <span class="thread-date" data-toggle="tooltip" title='{{ App\User::dateFormat($thread->created_at) }}'>{{ App\User::dateDiffForHumans($thread->created_at) }}</span>
                                </div>
                            </div>
                            <div class="thread-body">
                                {!! $thread->getCleanBody() !!}

                                @if ( $thread->opened_at )
                                    <div class='thread-opened-at'><i class="glyphicon glyphicon-eye-open"></i> {{ __("Customer viewed") }} {{ App\User::dateDiffForHumansWithHours($thread->opened_at) }}</div>
                                @endif
                            </div>
                            @include('conversations/partials/thread_attachments')
                        </div>
                    </div>
                @else
                    <div class="thread thread-type-{{ $thread->getTypeName() }}" id="thread-{{ $thread->id }}">
                        <div class="thread-photo">
                            @include('partials/person_photo', ['person' => $thread->getPerson(true)])
                        </div>
                        <div class="thread-message">
                            <div class="thread-header">
                                <div class="thread-title">
                                    <div class="thread-person">
                                        <strong>
                                            @if ($thread->type == App\Thread::TYPE_CUSTOMER)
                                                <a href="{{ $thread->customer->url() }}">{{ $thread->customer->getFullName(true) }}</a>
                                            @else
                                                @include('conversations/thread_by', ['as_link' => true])
                                            @endif
                                        </strong> 
                                        {{-- Lines below must be spaceless --}}
                                            {{--@if ($loop->last)
                                            {{ __("started the conversation") }}@elseif ($thread->type == App\Thread::TYPE_NOTE)
                                            {{ __("added a note") }}@else
                                            {{ __("replied") }}@endif--}}{{ \Eventy::action('thread.after_person_action', $thread, $loop, $threads, $conversation, $mailbox) }}
                                    </div>
                                    @if ($thread->type != App\Thread::TYPE_NOTE)
                                        <div class="thread-recipients">
                                            @if ($loop->last 
                                                || ($thread->type == App\Thread::TYPE_CUSTOMER && count($thread->getToArray($mailbox->getEmails())))
                                                || ($thread->type == App\Thread::TYPE_MESSAGE && !in_array($conversation->customer_email, $thread->getToArray()))
                                                || ($thread->type == App\Thread::TYPE_MESSAGE && count($customer->emails) > 1)
                                            )
                                                <div>
                                                    <strong>
                                                        {{ __("To") }}:
                                                    </strong>
                                                    {{ implode(', ', $thread->getToArray()) }}
                                                </div>
                                            @endif
                                            @if ($thread->getCcArray($mailbox->getEmails()))
                                                <div>
                                                    <strong>
                                                        {{ __("Cc") }}:
                                                    </strong>
                                                    {{ implode(', ', $thread->getCcArray()) }}
                                                </div>
                                            @endif
                                            @if ($thread->getBccArray($mailbox->getEmails()))
                                                <div>
                                                    <strong>
                                                        {{ __("Bcc") }}:
                                                    </strong>
                                                    {{ implode(', ', $thread->getBccArray()) }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="thread-info">
                                    @if ($thread->type == App\Thread::TYPE_NOTE)
                                        {{--<span class="thread-type">{{ __('Note') }} <span>·</span> </span>--}}
                                    @else
                                        @if (in_array($thread->type, [App\Thread::TYPE_CUSTOMER, App\Thread::TYPE_MESSAGE]))
                                            @php
                                                if (!empty($thread_num)) {
                                                    $thread_num--;
                                                } else {
                                                    $thread_num = $conversation->threads_count;
                                                }
                                                if (!isset($is_first) && ($thread->type == App\Thread::TYPE_CUSTOMER || $thread->type == App\Thread::TYPE_MESSAGE)) {
                                                    $is_first = true;
                                                } elseif (isset($is_first)) {
                                                    $is_first = false;
                                                }
                                            @endphp
                                            @if (!empty($is_first) && $conversation->threads_count > 2)<a href="#thread-{{ $threads[count($threads)-1]->id }}" class="thread-to-first" data-toggle="tooltip" title="{{ __('Scroll to the Beginning') }}"><i class="glyphicon glyphicon-arrow-down"></i> </a>@endif
                                            <span class="thread-type">({{ $thread_num }}) <span>·</span> </span>
                                        @endif
                                    @endif
                                    <span class="thread-date" data-toggle="tooltip" title='{{ App\User::dateFormat($thread->created_at) }}'>{{ App\User::dateDiffForHumans($thread->created_at) }}</span><br/>
                                    {{--<a href="#thread-{{ $thread->id }}">#{{ $thread_index+1 }}</a>--}}
                                    @if (in_array($thread->type, [App\Thread::TYPE_CUSTOMER, App\Thread::TYPE_MESSAGE]))
                                        <span class="thread-status">
                                            @if ($loop->last || (!$loop->last && $thread->status != App\Thread::STATUS_NOCHANGE && $thread->status != $threads[$loop->index+1]->status))
                                                @php
                                                    $show_status = true;
                                                @endphp
                                            @else
                                                @php
                                                    $show_status = false;
                                                @endphp
                                            @endif
                                            @if ($loop->last || (!$loop->last && ($thread->user_id != $threads[$loop->index+1]->user_id || $threads[$loop->index+1]->action_type == App\Thread::ACTION_TYPE_USER_CHANGED))
                                            )
                                                @if ($thread->user_id)
                                                    @if ($thread->user_cached)
                                                        {{ $thread->user_cached->getFullName() }}@if (!empty($show_status)),@endif
                                                    @endif
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

                                @if ( $thread->opened_at )
                                    <div class='thread-opened-at'><i class="glyphicon glyphicon-eye-open"></i> {{ __("Customer viewed") }} {{ App\User::dateDiffForHumansWithHours($thread->opened_at) }}</div>
                                @endif

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
                        </div>
                        <div class="dropdown thread-options">
                            <span class="dropdown-toggle glyphicon glyphicon-option-vertical" data-toggle="dropdown"></span>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                {{--<li><a href="#" title="" class="thread-edit-trigger">{{ __("Edit") }} (todo)</a></li>
                                <li><a href="javascript:alert('todo: implement hiding threads');void(0);" title="" class="thread-hide-trigger">{{ __("Hide") }} (todo)</a></li>--}}
                                <li><a href="{{ route('conversations.create', ['mailbox_id' => $mailbox->id]) }}?from_thread_id={{ $thread->id }}" title="{{ __("Start a conversation from this thread") }}" class="new-conv">{{ __("New Conversation") }}</a></li>
                                @if (Auth::user()->isAdmin())
                                    <li><a href="{{ route('conversations.ajax_html', ['action' => 
                                        'send_log']) }}?thread_id={{ $thread->id }}" title="{{ __("View outgoing emails") }}" data-trigger="modal" data-modal-title="{{ __("Outgoing Emails") }}" data-modal-size="lg">{{ __("Outgoing Emails") }}</a></li>
                                @endif
                                @if ($thread->isReply())
                                    <li><a href="{{ route('conversations.ajax_html', ['action' => 
                                        'show_original']) }}?thread_id={{ $thread->id }}" title="{{ __("Show original message") }}" data-trigger="modal" data-modal-title="{{ __("Original Message") }}" data-modal-fit="true" data-modal-size="lg">{{ __("Show Original") }}</a></li>
                                @endif
                                {{--@if (in_array($thread->type, [App\Thread::TYPE_CUSTOMER, App\Thread::TYPE_MESSAGE]))
                                    <li class="divider"></li>
                                    <li>
                                        <span>
                                        @if ($loop->last || $thread->status != App\Thread::STATUS_NOCHANGE)
                                            @php
                                                $show_status = true;
                                            @endphp
                                        @endif
                                        @if ($loop->last || (!$loop->last && ($thread->user_id != $threads[$loop->index+1]->user_id || $threads[$loop->index+1]->action_type == App\Thread::ACTION_TYPE_USER_CHANGED))
                                        )
                                            @if ($thread->user_id)
                                                @if ($thread->user_cached)
                                                    {{ __("Assigned:") }} <strong>{{ $thread->user_cached->getFullName() }}</strong>@if (!empty($show_status))<br/>@endif
                                                @endif
                                            @else
                                                {{ __("Assigned:") }} <strong>{{ __("Anyone") }}</strong>@if (!empty($show_status))<br/>@endif
                                            @endif
                                        @endif
                                        @if (!empty($show_status))
                                            {{ __("Status:") }} <strong>{{ $thread->getStatusName() }}</strong>
                                        @endif
                                        </span>
                                    </li>
                                @endif--}}
                            </ul>
                        </div>
                        {{--@if (in_array($thread->type, [App\Thread::TYPE_CUSTOMER, App\Thread::TYPE_MESSAGE]))
                            @php
                                if (!empty($thread_num)) {
                                    $thread_num--;
                                } else {
                                    $thread_num = $conversation->threads_count;
                                }
                            @endphp
                            <div class="thread-type"><i class="glyphicon glyphicon-share-alt"></i> {{ $thread_num }}</div>
                        @endif--}}
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    newConversationInit();
    initConversation();
@endsection