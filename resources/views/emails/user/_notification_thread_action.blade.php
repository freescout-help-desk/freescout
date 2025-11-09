@if ($thread->type == App\Thread::TYPE_NOTE)
    <span style="color:#e6b216">
        {!! __(':person added a note', ['person' => '<strong style="color:#000000;">'.$thread->getCreatedBy()->getFullName(true).'</strong>']) !!}
    </span>
@else
    @if ($thread->type == App\Thread::TYPE_MESSAGE)
        @php
            $action_color = config('app.colors')['text_user'];
        @endphp
    @else
        @php
            $action_color = config('app.colors')['text_customer'];
        @endphp
    @endif
    <span style="color:{{ $action_color }}">
        @if ($thread->isForwarded())
            @php $trans_text = __(':person forwarded a conversation :forward_parent_conversation_number') @endphp
        @elseif ($loop->last)
            @php $trans_text = __(':person started the conversation') @endphp
        @else
            @php $trans_text = __(':person replied') @endphp
        @endif
        @php
            $trans_params = ['person' => '<strong style="color:#000000;">'.$thread->getCreatedBy()->getFullName(true).'</strong>'];
            if ($thread->isForwarded()) {
                $trans_params['forward_parent_conversation_number'] = '<a href="'.route('conversations.view', ['id' => $thread->getMetaFw(App\Thread::META_FORWARD_PARENT_CONVERSATION_ID)]).'#thread-'.$thread->getMetaFw(App\Thread::META_FORWARD_PARENT_THREAD_ID).'">#'.$thread->getMetaFw(App\Thread::META_FORWARD_PARENT_CONVERSATION_NUMBER).'</a>';
            }
        @endphp
        {!! __($trans_text, $trans_params) !!}
    </span>
@endif