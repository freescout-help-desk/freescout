{{-- todo: cache --}}
@php
    $mailboxes = Auth::user()->mailboxesCanView();
    $mailbox_id = null;

    if (!empty($mailboxes[0])) {
        $mailbox_id = $mailboxes[0]->id;
    }

    if (!empty(app('request')->mailbox_id)) {
        $mailbox_id = app('request')->mailbox_id;
    } elseif (preg_match("/^mailboxes/", \Request::route()->getName() ?: '') && !empty(app('request')->id)) {
        $mailbox_id = app('request')->id;
    } elseif (\Helper::getGlobalEntity('mailbox')) {
        $mailbox_id = \Helper::getGlobalEntity('mailbox')->id;
    }
@endphp

<li class="dropdown {{ \App\Misc\Helper::menuSelectedHtml('knowledgebase') }}">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
        {{ __('Knowledge Base') }} <span class="caret"></span>
    </a>

    <ul class="dropdown-menu">
        <li class="{{ \App\Misc\Helper::menuSelectedHtml('mailboxes.knowledgebase.settings') }}"><a href="@if (!count($mailboxes) || !$mailbox_id){{ route('mailboxes.create') }}@else{{ route('mailboxes.knowledgebase.settings', ['mailbox_id'=>$mailbox_id]) }}@endif">{{ __('Settings') }}</a></li>
        <li class="{{ \App\Misc\Helper::menuSelectedHtml('mailboxes.knowledgebase.categories') }}"><a href="@if (!count($mailboxes) || !$mailbox_id){{ route('mailboxes.create') }}@else{{ route('mailboxes.knowledgebase.categories', ['mailbox_id'=>$mailbox_id]) }}@endif">{{ __('Categories') }}</a></li>
        <li class="{{ \App\Misc\Helper::menuSelectedHtml('mailboxes.knowledgebase.articles') }}"><a href="@if (!count($mailboxes) || !$mailbox_id){{ route('mailboxes.create') }}@else{{ route('mailboxes.knowledgebase.articles', ['mailbox_id'=>$mailbox_id]) }}@endif">{{ __('Articles') }}</a></li>
    </ul>
</li>