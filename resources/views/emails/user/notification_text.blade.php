{{ App\Misc\Mail::REPLY_SEPARATOR_TEXT }}

@if (count($threads) == 1){{ __('Received a new conversation') }}@else @if ($thread->action_type == App\Thread::ACTION_TYPE_STATUS_CHANGED){{ __(":person marked as :status conversation". ['person' => $thread->getCreatedBy()->getFullName(true), 'status' => $thread->getStatusName()]) }}@elseif ($thread->action_type == App\Thread::ACTION_TYPE_USER_CHANGED)
@include('emails/user/thread_by') {{ __("assigned to :person conversation", ['person' => $thread->getAssigneeName(false, $user)]) }}@else
{{ __(":person replied to conversation", ['person' => $thread->getCreatedBy()->getFullName(true)]) }}@endif @endif #{{ $conversation->number }}

@foreach ($threads as $thread)
-----------------------------------------------------------
@if ($thread->type == App\Thread::TYPE_LINEITEM)
## @include('emails/user/thread_by') @if ($thread->action_type == App\Thread::ACTION_TYPE_STATUS_CHANGED) {{ __("marked as") }} {{ $thread->getStatusName() }} @elseif ($thread->action_type == App\Thread::ACTION_TYPE_USER_CHANGED){{ __("assigned to") }} {{ $thread->getAssigneeName(false, $user) }}@endif, {{ __('on :date', ['date' => App\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}
@else
@if ($thread->type == App\Thread::TYPE_NOTE)
## {{ $thread->getCreatedBy()->getFullName(true) }} {{ __('added a note') }}, {{ __('on :date', ['date' => App\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}@else
## {{ $thread->getCreatedBy()->getFullName(true) }} @if ($loop->last){{ __('started the conversation') }}@else {{ __('replied') }}@endif, {{ __('on :date', ['date' => App\Customer::dateFormat($thread->created_at, 'M j @ H:i').' ('.\Config::get('app.timezone').')' ]) }}@endif:
{{ (new Html2Text\Html2Text($thread->body))->getText() }}@endif
@if ($thread->has_attachments)
{{ __('Attached:') }}
@foreach ($thread->attachments as $i => $attachment)
{{ ($i+1) }}) {{ $attachment->file_name }} [{{ $attachment->url() }}]
@endforeach
@endif
@endforeach


{{ __('Conversation URL:') }} {{ $conversation->url() }}

--
{{ __('Reply with any of these commands to update the conversation:') }}
https://git.io/fNybs (todo)

-----------------------------------------------------------

FreeScout:
{{ \Config::get('app.freescout_url') }}