<ul class="nav nav-tabs nav-tabs-main">
    <li @if (Route::is('mailboxes.sat_ratings'))class="active"@endif><a href="{{ route('mailboxes.sat_ratings', ['id'=>$mailbox->id]) }}">{{ __('Settings') }}</a></li>
    <li @if (Route::is('mailboxes.sat_ratings_trans'))class="active"@endif><a href="{{ route('mailboxes.sat_ratings_trans', ['id'=>$mailbox->id]) }}">{{ __('Translate') }}</a></li>
</ul>