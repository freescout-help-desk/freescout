<div class="alert alert-warning text-larger text-center">{{ __('This content is not available.') }}
@if (!empty($mailbox))
    <br/><a href="{{ \Kb::getKbUrl($mailbox) }}">« {{ 'Home'}}</a>
@endif</div>