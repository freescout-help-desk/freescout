<ul id="connection-settings" class="nav nav-tabs nav-tabs-main">
    <li @if (Route::currentRouteName() == 'mailboxes.knowledgebase.settings')class="active"@endif><a href="{{ route('mailboxes.knowledgebase.settings', ['id'=>$mailbox->id]) }}">{{ __('Settings') }}</a></li>
    <li @if (Route::currentRouteName() == 'mailboxes.knowledgebase.categories')class="active"@endif><a href="{{ route('mailboxes.knowledgebase.categories', ['id'=>$mailbox->id]) }}">{{ __('Categories') }}</a></li>
    <li @if (in_array(Route::currentRouteName(), ['mailboxes.knowledgebase.articles', 'mailboxes.knowledgebase.article', 'mailboxes.knowledgebase.new_article']))class="active"@endif><a href="{{ route('mailboxes.knowledgebase.articles', ['id'=>$mailbox->id]) }}">{{ __('Articles') }}</a></li>
</ul>