@extends('layouts.app')

@php
    $is_in_chat_mode = $conversation->isInChatMode();
@endphp

@section('title_full', '#'.$conversation->number.' '.$conversation->getSubject().($customer ? ' - '.$customer->getFullName(true) : ''))

@if (app('request')->input('print'))
    @section('body_class', 'body-conv print')
@else
    @section('body_class', 'body-conv'.($is_in_chat_mode ? ' chat-mode' : ''))
@endif

@section('body_attrs')@parent data-conversation_id="{{ $conversation->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')

    <div id="conv-layout" class="conv-type-{{ strtolower($conversation->getTypeName()) }} @if ($is_following) conv-following @endif">
        <div id="conv-layout-header">
            <div id="conv-toolbar">

                <div class="conv-actions">
                    @php
                        $actions = \App\Misc\ConversationActionButtons::getActions($conversation, Auth::user(), $mailbox);
                        $toolbar_actions = \App\Misc\ConversationActionButtons::getActionsByLocation($actions, \App\Misc\ConversationActionButtons::LOCATION_TOOLBAR);
                        $dropdown_actions = \App\Misc\ConversationActionButtons::getActionsByLocation($actions, \App\Misc\ConversationActionButtons::LOCATION_DROPDOWN);
                    @endphp

                    {{-- There should be no spaces between buttons --}}
                    @foreach ($toolbar_actions as $action_key => $action)
                        @if ($action_key === 'delete')
                            {{-- Special handling for delete button --}}
                            <span class="hidden-xs {{ $action['class'] }} conv-action glyphicon {{ $action['icon'] }}"
                                  data-toggle="tooltip"
                                  data-placement="bottom"
                                  title="{{ $action['label'] }}"
                                  aria-label="{{ $action['label'] }}"
                                  role="button"></span>
                        @elseif (!empty($action['url']))
                            {{-- Action with URL (like move, merge) --}}
                            <a href="{{ $action['url']($conversation) }}"
                               class="{{ $action['class'] }} conv-action"
                               role="button"
                               @if (!empty($action['mobile_only']))class="hidden-xs"
                        @endif
                        @if (!empty($action['attrs']))
                            @foreach ($action['attrs'] as $attr_key => $attr_value)
                                {{ $attr_key }}="{{ $attr_value }}"
                            @endforeach
                        @endif
                        data-toggle="tooltip"
                        data-placement="bottom"
                        title="{{ $action['label'] }}"
                        aria-label="{{ $action['label'] }}">
                        <i class="glyphicon {{ $action['icon'] }}"></i>
                        </a>
                        @else
                            {{-- Simple button action --}}
                            <span class="@if (!empty($action['mobile_only']))hidden-xs @endif {{ $action['class'] }} conv-action glyphicon {{ $action['icon'] }}"
                                  data-toggle="tooltip"
                                  data-placement="bottom"
                                  title="{{ $action['label'] }}"
                                  aria-label="{{ $action['label'] }}"
                                  role="button"
                            @if (!empty($action['attrs']))
                                @foreach ($action['attrs'] as $attr_key => $attr_value)
                                    {{ $attr_key }}="{{ $attr_value }}"
                                @endforeach
                            @endif
                            ></span>
                        @endif
                    @endforeach

                    @action('conversation.action_buttons', $conversation, $mailbox)

                    {{-- More Actions Dropdown --}}
                    <div class="dropdown conv-action" data-toggle="tooltip" title="{{ __('More Actions') }}">
        <span class="conv-action glyphicon glyphicon-option-horizontal dropdown-toggle"
              data-toggle="dropdown"
              role="button"
              aria-haspopup="true"
              aria-expanded="false"
              aria-label="{{ __('More Actions') }}"></span>
                        <ul class="dropdown-menu dropdown-with-icons">
                            @action('conversation.prepend_action_buttons', $conversation, $mailbox)
                            @foreach ($dropdown_actions as $action_key => $action)
                                @if ($action_key === 'delete_mobile')
                                    <li class="hidden-lg hidden-md hidden-sm">
                                        <a href="#" class="{{ $action['class'] }}" role="button">
                                            <i class="glyphicon {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                        </a>
                                    </li>
                                @else
                                    <li>
                                        @if (!empty($action['has_opposite']))
                                            <a href="#" class="{{ $action['class'] }} @if ($is_following) hidden @endif" data-follow-action="follow" role="button">
                                                <i class="glyphicon {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                            </a>
                                            <a href="#" class="{{ $action['opposite']['class'] }} @if (!$is_following) hidden @endif" data-follow-action="unfollow" role="button">
                                                <i class="glyphicon {{ $action['icon'] }}"></i> {{ $action['opposite']['label'] }}
                                            </a>
                                        @else
                                            <a href="{{ !empty($action['url']) ? $action['url']($conversation) : '#' }}"
                                               class="{{ $action['class'] }}"
                                               role="button"
                                            @if (!empty($action['attrs']))
                                                @foreach ($action['attrs'] as $attr_key => $attr_value)
                                                    {{ $attr_key }}="{{ $attr_value }}"
                                                @endforeach
                                            @endif
                                            >
                                            <i class="glyphicon {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                            </a>
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                            @action('conversation.append_action_buttons', $conversation, $mailbox)
                        </ul>
                    </div>
                </div>

                <ul class="conv-info">
                    @action('conversation.convinfo.prepend', $conversation, $mailbox)
                    @if ($conversation->state != App\Conversation::STATE_DELETED)
                        <li>
                            <div class="btn-group" id="conv-assignee" data-toggle="tooltip" title="{{ __("Assignee") }}: {{ $conversation->getAssigneeName(true) }}">
                                <button type="button" class="btn btn-default conv-info-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-title="{{ __("Assignee") }}" aria-hidden="true"><i class="glyphicon glyphicon-user"></i></button>
                                <button type="button" class="btn btn-default dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-title="{{ __("Assignee") }}" aria-label="{{ __("Assignee") }}: {{ $conversation->getAssigneeName(true) }}">
                                    <span>{{ $conversation->getAssigneeName(true) }}</span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu conv-user dm-scrollable">
                                    <li @if (!$conversation->user_id) class="active" @endif><a href="#" data-user_id="-1">{{ __("Anyone") }}</a></li>
                                    <li @if ($conversation->user_id == Auth::user()->id) class="active" @endif><a href="#" data-user_id="{{ Auth::user()->id }}">{{ __("Me") }}</a></li>
                                    @foreach ($mailbox->usersAssignable() as $user)
                                        @if ($user->id != Auth::user()->id)
                                            @php
                                                $a_class = \Eventy::filter('assignee_list.a_class', '', $user);
                                            @endphp
                                            <li @if ($conversation->user_id == $user->id) class="active" @endif><a href="#" data-user_id="{{ $user->id }}" @if ($a_class) class="{{ $a_class }}"@endif>{{ $user->getFullName() }}@action('assignee_list.item_append', $user)</a></li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endif
                    <li>
                        <div class="btn-group" id="conv-status" data-toggle="tooltip" title="{{ __("Status") }}: {{ $conversation->getStatusName() }}">
                            @if ($conversation->state != App\Conversation::STATE_DELETED)
                                <button type="button" class="btn btn-{{ App\Conversation::$status_classes[$conversation->getStatus()] }} btn-light conv-info-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __("Status") }}: {{ $conversation->getStatusName() }}"><i class="glyphicon glyphicon-{{ App\Conversation::$status_icons[$conversation->getStatus()] }}"></i></button>
                                <button type="button" class="btn btn-{{ App\Conversation::$status_classes[$conversation->getStatus()] }} btn-light dropdown-toggle conv-info-val" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __("Status") }}">
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
                    </li>@action('conversation.convinfo.before_nav', $conversation, $mailbox)<li class="conv-next-prev">
                        <a href="{{ $conversation->urlPrev(App\Conversation::getFolderParam()) }}" class="glyphicon glyphicon-menu-left" data-toggle="tooltip" title="{{ __("Newer") }}"></a>
                        <a href="{{ $conversation->urlNext(App\Conversation::getFolderParam()) }}" class="glyphicon glyphicon-menu-right" data-toggle="tooltip" title="{{ __("Older") }}"></a>
                    </li>
                </ul>

                <div class="clearfix"></div>

            </div>
            <div id="conv-subject">
                <div class="conv-subj-block">
                    <div class="conv-subjwrap">
                        <div class="conv-subjtext">
                            <span>{{ $conversation->getSubject() }}</span>
                            <div class="input-group input-group-lg conv-subj-editor">
                                <input type="text" id="conv-subj-value" class="form-control" value="{{ $conversation->getSubject() }}" />
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="button" data-loading-text="…"><i class="glyphicon glyphicon-ok"></i></button>
                                </span>
                            </div>
                        </div>
                        @if ($conversation->isChat() && $conversation->getChannelName())
                            <span class="conv-tags">
                                @if (\Helper::isChatMode())<a class="btn btn-default fs-tag-btn" href="{{ request()->fullUrlWithQuery(['chat_mode' => '0']) }}" title="{{ __('Exit') }}" data-toggle="tooltip"><small class="glyphicon glyphicon-stop"></small> {{ __('Chat Mode') }}</a>@else<a class="btn btn-primary fs-tag-btn" href="{{ request()->fullUrlWithQuery(['chat_mode' => '1']) }}"><small class="glyphicon glyphicon-play"></small> {{ __('Chat Mode') }}</a>@endif<span class="fs-tag fs-tag-md"><a class="fs-tag-name" href="#"><small class="glyphicon glyphicon-phone"></small> {{ $conversation->getChannelName() }}</a></span>
                            </span>
                        @endif
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
                @if ($is_in_chat_mode)
                    <div class="conv-top-block conv-top-chat clearfix">
                        @if ($conversation->user_id != Auth::user()->id)
                            <button class="btn btn-success btn-xs pull-right chat-accept" data-loading-text="{{ __('Accept Chat') }}…">{{ __('Accept Chat') }}</button>
                        @elseif (!$conversation->isClosed())
                            <button class="btn btn-default btn-xs pull-right chat-end" data-loading-text="{{ __('End Chat') }}…">{{ __('End Chat') }}</button>
                        @endif
                        <a href="#conv-top-blocks" data-toggle="collapse">{{ __('Show Details') }} <b class="caret"></b></a>
                    </div>
                    <div class="collapse" id="conv-top-blocks">
                @endif
                    @action('conversation.after_subject_block', $conversation, $mailbox)
                @if ($conversation->isInChatMode())
                    </div>
                @endif
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
                                <input type="hidden" name="conv_history" value=""/>

                                @if (count($from_aliases))
                                    <div class="form-group conv-from-alias">
                                        <label class="control-label">{{ __('From') }}</label>

                                        <div class="conv-reply-field">
                                            <select name="from_alias" class="form-control">
                                                @foreach ($from_aliases as $from_alias_email => $from_alias_name)
                                                    <option value="@if ($from_alias_email != $mailbox->email){{ $from_alias_email }}@endif" @if (!empty($from_alias) && $from_alias == $from_alias_email)selected="selected"@endif>@if ($from_alias_name){{ $from_alias_email }} ({{ $from_alias_name }})@else{{ $from_alias_email }}@endif</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif

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
                                        <select class="form-control hidden parsley-exclude draft-changer" name="to_email[]" id="to_email" multiple required autofocus>
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
                                        <a href="#" class="help-link" id="toggle-cc">Cc/Bcc</a>
                                    </div>
                                </div>

                                @if (!empty($threads[0]) && $threads[0]->type == App\Thread::TYPE_NOTE && $threads[0]->created_by_user_id != Auth::user()->id && $threads[0]->created_by_user)
                                    <div class="alert alert-warning alert-switch-to-note">
                                        <i class="glyphicon glyphicon-exclamation-sign"></i>
                                        {!! __('This reply will go to the customer. :%switch_start%Switch to a note:%switch_end% if you are replying to :user_name.', ['%switch_start%' => '<a href="#" class="switch-to-note">', '%switch_end%' => '</a>', 'user_name' => htmlspecialchars($threads[0]->created_by_user->getFullName()) ]) !!}
                                    </div>
                                @endif

                                <div class="thread-attachments attachments-upload form-group">
                                    <ul></ul>
                                </div>

                                <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }} conv-reply-body">
                                    <textarea id="body" class="form-control" name="body" rows="13" data-parsley-required="true" data-parsley-required-message="{{ __('Please enter a message') }}" @if ($conversation->isInChatMode()) placeholder="{{ __('Use ENTER to send the message and SHIFT+ENTER for a new line') }}" @endif>{{ old('body', $conversation->body) }}</textarea>
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
            @include('conversations/partials/customer_sidebar')
            @action('conversation.after_customer_sidebar', $conversation)
            IP:{{$conversation->ip}}<br>
            Source:{{$conversation->source}}<br>
            Path:{{$conversation->path}}<br>
        </div>
        <div id="conv-layout-main">
            @include('conversations/partials/threads')
            @action('conversation.after_threads', $conversation)
        </div>
    </div>
@endsection

@section('body_bottom')
    @parent
    @include('conversations.partials.settings_modal', ['conversation' => $conversation])
@append

@include('partials/editor')

@section('javascript')
    @parent
    initReplyForm();
    initConversation();
@endsection
