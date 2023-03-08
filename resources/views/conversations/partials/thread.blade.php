@if ($thread->type == App\Thread::TYPE_LINEITEM)
    <div class="thread thread-type-{{ $thread->getTypeName() }} thread-state-{{ $thread->getStateName() }}" id="thread-{{ $thread->id }}">
        <div class="thread-message">
            <div class="thread-header">
                <div class="thread-title">
                    {!! $thread->getActionText('', true, false, null, view('conversations/thread_by', ['thread' => $thread])->render()) !!}
                </div>
                <div class="thread-info">
                    <a href="#thread-{{ $thread->id }}" class="thread-date" data-toggle="tooltip" title='{{ App\User::dateFormat($thread->created_at) }}'>{{ App\User::dateDiffForHumans($thread->created_at) }}</a>
                </div>
            </div>
            @action('thread.after_header', $thread, $loop, $threads, $conversation, $mailbox)
        </div>
        <div class="dropdown thread-options">
            <span class="dropdown-toggle {{--glyphicon glyphicon-option-vertical--}}" data-toggle="dropdown"><b class="caret"></b></span>
            @if (Auth::user()->isAdmin())
                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    @action('thread.menu', $thread)
                    <li><a href="{{ route('conversations.ajax_html', array_merge(['action' =>
                        'send_log'], \Request::all(), ['thread_id' => $thread->id])) }}" title="{{ __("View outgoing emails") }}" data-trigger="modal" data-modal-title="{{ __("Outgoing Emails") }}" data-modal-size="lg">{{ __("Outgoing Emails") }}</a></li>
                    @action('thread.menu.append', $thread)
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
                        <strong>@include('conversations/thread_by')</strong>
                        @if ($thread->isForward())
                            {{ __('are forwarding') }}
                        @else
                            &nbsp;
                        @endif
                        [{{ __('Draft') }}]
                    </div>
                    <div class="btn-group btn-group-xs draft-actions">
                        <a class="btn btn-default edit-draft-trigger" href="javascript:void(0);">{{ __('Edit') }}</a>
                        <a class="btn btn-default discard-draft-trigger" href="javascript:void(0)">{{ __('Discard') }}</a>
                    </div>
                </div>
                <div class="thread-info">
                    {{--<span class="thread-type">[{{ __('Draft') }}] <span>·</span> </span>--}}
                    <a href="#thread-{{ $thread->id }}" class="thread-date" data-toggle="tooltip" title='{{ App\User::dateFormat($thread->created_at) }}'>{{ App\User::dateDiffForHumans($thread->created_at) }}</a>
                </div>
            </div>
            @action('thread.after_header', $thread, $loop, $threads, $conversation, $mailbox)
            <div class="thread-body">
                @action('thread.before_body', $thread, $loop, $threads, $conversation, $mailbox)
                {!! $thread->getCleanBody() !!}

                @include('conversations/partials/thread_attachments')
            </div>
        </div>
    </div>
@else
    <div class="thread thread-type-{{ $thread->getTypeName() }}" id="thread-{{ $thread->id }}" data-thread_id="{{ $thread->id }}">
        <div class="thread-photo">
            @include('partials/person_photo', ['person' => $thread->getPerson(true)])
        </div>
        <div class="thread-message">
            @if ($thread->isForward())
                <div class="thread-badge">
                    <i class="glyphicon glyphicon-arrow-right"></i>
                </div>
            @endif
            @if ($conversation->isPhone() && $thread->first)
                <div class="thread-badge">
                    <i class="glyphicon glyphicon-earphone"></i>
                </div>
            @endif
            <div class="thread-header">
                <div class="thread-title">
                    <div class="thread-person">
                        <strong>
                            @if ($thread->type == App\Thread::TYPE_CUSTOMER)
                                @if ($thread->customer_cached)
                                    @if (\Helper::isPrint())
                                        {{ $thread->customer_cached->getFullName(true) }}
                                    @else
                                        <a href="{{ $thread->customer_cached->url() }}">{{ $thread->customer_cached->getFullName(true) }}</a>
                                    @endif
                                @endif
                            @else
                                @if (\Helper::isPrint())
                                    {{ $thread->created_by_user_cached->getFullName() }}
                                @else
                                    @include('conversations/thread_by', ['as_link' => true])
                                @endif
                            @endif
                        </strong>
                        @if (\Helper::isPrint())
                            <small>&lt;{{ ($thread->type == App\Thread::TYPE_CUSTOMER ? $thread->customer_cached->getMainEmail() : $thread->created_by_user_cached->email ) }}&gt;</small>
                            @if ($thread->isNote())
                                [{{ __('Note') }}]
                            @endif
                        @endif
                        {{-- Lines below must be spaceless --}}
                        {{ \Eventy::action('thread.after_person_action', $thread, $loop, $threads, $conversation, $mailbox) }}
                    </div>
                    @if ($thread->type != App\Thread::TYPE_NOTE || $thread->isForward())
                        <div class="thread-recipients">
                            @action('thread.before_recipients', $thread, $loop, $threads, $conversation, $mailbox)
                            @if (($thread->isForward()
                                || $loop->last
                                || ($thread->type == App\Thread::TYPE_CUSTOMER && count($thread->getToArray($mailbox->getEmails())))
                                || ($thread->type == App\Thread::TYPE_MESSAGE && !in_array($conversation->customer_email, $thread->getToArray()))
                                || ($thread->type == App\Thread::TYPE_MESSAGE && count($customer->emails) > 1)
                                || \Helper::isPrint())
                                && $thread->getToArray()
                            )
                                <div>
                                    <strong>
                                        {{ __("To") }}:
                                    </strong>
                                    {{ implode(', ', $thread->getToArray()) }}
                                </div>
                            @endif
                            @if ($thread->getCcArray())
                                <div>
                                    <strong>
                                        {{ __("Cc") }}:
                                    </strong>
                                    {{ implode(', ', $thread->getCcArray()) }}
                                </div>
                            @endif
                            @if ($thread->getBccArray())
                                <div>
                                    <strong>
                                        {{ __("Bcc") }}:
                                    </strong>
                                    {{ implode(', ', $thread->getBccArray()) }}
                                </div>
                            @endif
                            @action('thread.after_recipients', $thread, $loop, $threads, $conversation, $mailbox)
                        </div>
                    @endif
                </div>
                <div class="thread-info">
                    @action('thread.info.prepend', $thread)
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
                            @if (!empty($is_first) && $conversation->threads_count > 2)<a href="#thread-{{ $threads[count($threads)-1]->id }}" class="thread-to-first" data-toggle="tooltip" title="{{ __('To the First Message') }}"><i class="glyphicon glyphicon-arrow-down"></i> </a>@endif
                            {{--<span class="thread-type">#{{ $thread_num }} <span>·</span> </span>--}}
                        @endif
                    @endif
                    @if (!\Helper::isPrint())
                        <a href="#thread-{{ $thread->id }}" class="thread-date" data-toggle="tooltip" title='{{ App\User::dateFormat($thread->created_at) }}'>{{ App\User::dateDiffForHumans($thread->created_at) }}</a><br/>
                    @else
                        <a href="#thread-{{ $thread->id }}" class="thread-date" data-toggle="tooltip" title='{{ App\User::dateFormat($thread->created_at) }}'>{{ App\User::dateFormat($thread->created_at) }}</a><br/>
                    @endif
                    {{--<a href="#thread-{{ $thread->id }}">#{{ $thread_index+1 }}</a>--}}
                    @if (in_array($thread->type, [App\Thread::TYPE_CUSTOMER, App\Thread::TYPE_MESSAGE, App\Thread::TYPE_NOTE]))
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
            @action('thread.after_header', $thread, $loop, $threads, $conversation, $mailbox)
            <div class="thread-body">
                @php
                    $send_status_data = $thread->getSendStatusData();
                @endphp
                @if ($send_status_data)
                    @if (!empty($send_status_data['is_bounce']))
                        <div class="alert alert-warning">
                            @if (empty($send_status_data['bounce_for_thread']) || empty($send_status_data['bounce_for_conversation']))
                                {{ __('This is a bounce message.') }}
                            @else
                                @php
                                    $bounce_for_conversation = App\Conversation::find($send_status_data['bounce_for_conversation']);
                                @endphp
                                @if ($bounce_for_conversation)
                                    {!! __('This is a bounce message for :link', [
                                    'link' => '<a href="'.route('conversations.view', ['id' => $send_status_data['bounce_for_conversation']]).'#thread-id='.$send_status_data['bounce_for_thread'].'">#'.$bounce_for_conversation->number.'</a>'
                                    ]) !!}
                                @endif
                            @endif
                        </div>
                    @endif
                @endif
                @if ($thread->isSendStatusError())
                        <div class="alert alert-danger alert-light">
                            <div>
                                <strong>{{ __('Message not sent to customer') }}</strong> (<a href="{{ route('conversations.ajax_html', array_merge(['action' =>
                        'send_log'], \Request::all(), ['thread_id' => $thread->id]) ) }}" data-trigger="modal" data-modal-title="{{ __("Outgoing Emails") }}" data-modal-size="lg">{{ __('View log') }}</a>)
                            </div>

                            @if (!empty($send_status_data['bounced_by_thread']) && !empty($send_status_data['bounced_by_conversation']))
                                @php
                                    $bounced_by_conversation = App\Conversation::find($send_status_data['bounced_by_conversation']);
                                @endphp
                                @if ($bounced_by_conversation)
                                    <small>
                                        {!! __('Message bounced (:link)', [
                                        'link' => '<a href="'.route('conversations.view', ['id' => $send_status_data['bounced_by_conversation']]).'#thread-id='.$send_status_data['bounced_by_thread'].'">#'.$bounced_by_conversation->number.'</a>'
                                        ]) !!}
                                    </small>
                                @endif
                            @endif
                            @if (!empty($send_status_data['msg']))
                                <small>
                                    {{ $send_status_data['msg'] }}
                                </small>
                            @endif
                        </div>
                @endif
                @if ($thread->isForwarded())
                    <div class="alert alert-info">
                        {{ __('This is a forwarded conversation.') }}
                        {!! __('Original conversation: :forward_parent_conversation_number', [
                        'forward_parent_conversation_number' => '<a href="'.route('conversations.view', ['id' => $thread->getMetaFw(App\Thread::META_FORWARD_PARENT_CONVERSATION_ID)]).'#thread-'.$thread->getMetaFw(App\Thread::META_FORWARD_PARENT_THREAD_ID).'">#'.$thread->getMetaFw(App\Thread::META_FORWARD_PARENT_CONVERSATION_NUMBER).'</a>'
                        ]) !!}
                    </div>
                @endif
                @if ($thread->isForward())
                    <div class="alert alert-note">
                        {!! __(':person forwarded this conversation. Forwarded conversation: :forward_child_conversation_number', [
                        'person' => ucfirst($thread->getForwardByFullName()),
                        'forward_child_conversation_number' => '<a href="'.route('conversations.view', ['id' => $thread->getMetaFw(App\Thread::META_FORWARD_CHILD_CONVERSATION_ID)]).'">#'.$thread->getMetaFw(App\Thread::META_FORWARD_CHILD_CONVERSATION_NUMBER).'</a>'
                        ]) !!}
                    </div>
                @endif

                @action('thread.before_body', $thread, $loop, $threads, $conversation, $mailbox)

                <div class="thread-content" dir="auto">
                    {!! \Eventy::filter('thread.body_output', $thread->getBodyWithFormatedLinks(), $thread, $conversation, $mailbox) !!}
                </div>

                @if ($thread->body_original)
                    <div class='thread-meta'>
                        <i class="glyphicon glyphicon-pencil"></i> {{ __("Edited by :whom :when", ['whom' => $thread->getEditedByUserName(), 'when' => App\User::dateDiffForHumansWithHours($thread->edited_at)]) }} &nbsp;<a href="#" class="thread-original-show help-link link-underlined">{{ __("Show original") }}</a><a href="#" class="thread-original-hide help-link link-underlined hidden">{{ __("Hide") }}</a>
                        <div class="thread-original thread-text hidden">{!! $thread->getCleanBodyOriginal() !!}</div>
                    </div>
                @endif
                @if ($thread->opened_at)
                    <div class='thread-meta'><i class="glyphicon glyphicon-eye-open"></i> {{ __("Customer viewed :when", ['when' => App\User::dateDiffForHumansWithHours($thread->opened_at)]) }}</div>
                @endif

                @action('thread.meta', $thread, $loop, $threads, $conversation, $mailbox)

                @include('conversations/partials/thread_attachments')
            </div>
        </div>
        <div class="dropdown thread-options">
            <span class="dropdown-toggle {{--glyphicon glyphicon-option-vertical--}}" data-toggle="dropdown"><b class="caret"></b></span>
            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                @if (Auth::user()->can('edit', $thread))
                    <li><a href="#" title="" class="thread-edit-trigger" role="button">{{ __("Edit") }}</a></li>
                @endif
                @if ($thread->isNote() && !$thread->first && Auth::user()->can('delete', $thread))
                    <li><a href="#" class="thread-delete-trigger" role="button" data-loading-text="{{ __("Delete") }}…">{{ __("Delete") }}</a></li>
                @endif
                {{--<li><a href="javascript:alert('todo: implement hiding threads');void(0);" title="" class="thread-hide-trigger">{{ __("Hide") }} (todo)</a></li>--}}
                <li><a href="{{ route('conversations.create', ['mailbox_id' => $mailbox->id]) }}?from_thread_id={{ $thread->id }}" title="{{ __("Start a conversation from this thread") }}" class="new-conv" role="button">{{ __("New Conversation") }}</a></li>
                @if ($thread->isCustomerMessage())
                    <li><a href="{{ route('conversations.clone_conversation', ['mailbox_id' => $mailbox->id, 'from_thread_id' => $thread->id]) }}" title="{{ __("Clone a conversation from this thread") }}" class="new-conv" role="button">{{ __("Clone Conversation") }}</a></li>
                @endif
                @action('thread.menu', $thread)
                @if (Auth::user()->isAdmin())
                    <li><a href="{{ route('conversations.ajax_html', array_merge(['action' =>
                        'send_log'], \Request::all(), ['thread_id' => $thread->id])) }}" title="{{ __("View outgoing emails") }}" data-trigger="modal" data-modal-title="{{ __("Outgoing Emails") }}" data-modal-size="lg" role="button">{{ __("Outgoing Emails") }}</a></li>
                @endif
                @if ($thread->isReply())
                    <li><a href="{{ route('conversations.ajax_html', array_merge(['action' =>
                        'show_original'], \Request::all(), ['thread_id' => $thread->id])) }}" title="{{ __("Show original message") }}" data-trigger="modal" data-modal-title="{{ __("Original Message") }}" data-modal-fit="true" data-modal-size="lg" role="button">{{ __("Show Original") }}</a></li>
                @endif
                @if ($thread->isReply() || $thread->isNote())
                    <li><a href="{{ \Request::getRequestUri() }}&amp;print_thread_id={{ $thread->id }}&amp;print=1" target="_blank" role="button">{{ __("Print") }}</a></li>
                @endif
                @action('thread.menu.append', $thread)
            </ul>
        </div>
    </div>
@endif
