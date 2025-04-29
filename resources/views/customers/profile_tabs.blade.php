<ul class="nav nav-tabs nav-tabs-main margin-top">
    <li @if (Route::currentRouteName() == 'customers.update')class="active"@endif><a href="{{ route('customers.update', ['id'=>$customer->id]) }}">{{ __('Edit Profile') }}</a></li>
    <li @if (Route::currentRouteName() == 'customers.conversations')class="active"@endif><a href="{{ route('customers.conversations', ['id'=>$customer->id]) }}">{{ __('Conversations') }}</a></li>
    @if (!empty($extra_tab))
    	<li class="active"><a href="#">{{ $extra_tab }}</a></li>
    @endif
    @action('customers.profile_tabs.append')
</ul>
