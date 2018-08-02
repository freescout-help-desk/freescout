-- Please reply above this line --
@foreach ($threads as $thread)
-----------------------------------------------------------
## {{ $thread->getCreatedBy()->first_name }} @if ($loop->last){{ __('sent a message') }}@else {{ __('replied') }}@endif, {{ __('on :date', ['date' => App\Customer::dateFormat($thread->created_at, 'M j @ H:i')]) }} ({{ \Config::get('app.timezone') }}):
{{ Html2Text\Html2Text::convert($thread->body)}}{{-- (new \Html2Text\Html2Text($thread->body))->getText() --}}

{{ Html2Text\Html2Text::convert($conversation->mailbox->signature) }}{{-- (new \Html2Text\Html2Text($conversation->mailbox->signature))->getText() --}}
@endforeach