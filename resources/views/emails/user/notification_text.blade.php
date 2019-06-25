{{ App\Misc\Mail::REPLY_SEPARATOR_TEXT }}

@if (count($threads) == 1){{ __('Received a new conversation') }}@else @if ($thread->action_type == App\Thread::ACTION_TYPE_STATUS_CHANGED){{ __(":person marked as :status conversation". ['person' => $thread->getCreatedBy()->getFullName(true), 'status' => $thread->getStatusName()]) }}@elseif ($thread->action_type == App\Thread::ACTION_TYPE_USER_CHANGED)
@include('emails/user/thread_by') {{ __("assigned to :person conversation", ['person' => $thread->getAssigneeName(false, $user)]) }}@elseif ($thread->type == App\Thread::TYPE_NOTE){!! __(":person added a note to conversation", ['person' => '<strong>'.$thread->getCreatedBy()->getFullName(true).'</strong>']) !!}@else
{{ __(":person replied to conversation", ['person' => $thread->getCreatedBy()->getFullName(true)]) }}@endif @endif #{{ $conversation->number }}

@foreach ($threads as $thread)
-----------------------------------------------------------
@if ($thread->type == App\Thread::TYPE_LINEITEM)
## @include('emails/user/thread_by') {!! $thread->getActionText('', true, false, $user) !!}, {{ __('on :date', ['date' => App\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}
@else
@if ($thread->type == App\Thread::TYPE_NOTE)
## {!! __(':person added a note', ['person' => $thread->getCreatedBy()->getFullName(true)]) !!}, {{ __('on :date', ['date' => App\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}@else
## @if ($thread->isForwarded()){{ __(':person forwarded a conversation #:forward_parent_conversation_number', ['person' => $thread->getCreatedBy()->getFullName(true), 'forward_parent_conversation_number' => $thread->getMeta('forward_parent_conversation_number')]) }}@elseif ($loop->last){{ __(':person started the conversation', ['person' => $thread->getCreatedBy()->getFullName(true)]) }}@else{{ __(':person replied', ['person' => $thread->getCreatedBy()->getFullName(true)]) }}@endif, {{ __('on :date', ['date' => App\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}@endif:
@if ($thread->isForward()){!! __(':person forwarded this conversation. Forwarded conversation: :forward_child_conversation_number', ['person' => ucfirst($thread->getForwardByFullName()),'forward_child_conversation_number' => '#'.$thread->getMeta('forward_child_conversation_number')]) !!}
@endif{{ (new Html2Text\Html2Text($thread->body))->getText() }}
@endif
@if ($thread->has_attachments)
{{ __('Attached:') }}
@foreach ($thread->attachments as $i => $attachment)
{{ ($i+1) }}) {{ $attachment->file_name }} [{{ $attachment->url() }}]
@endforeach
@endif
@endforeach

{{ __('Conversation URL:') }} {{ $conversation->url() }}

{{ \Eventy::action('email_notification_text.footer_links', $mailbox, $conversation, $threads) }}

-----------------------------------------------------------

{{ $mailbox->name }}:
{{ $mailbox->url() }}