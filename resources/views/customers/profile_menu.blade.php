@php
    $profile_menu = \Eventy::filter('customer.profile_menu', '', $customer)
@endphp
<div class="dropdown customer-profile-menu">
    <a href="#" class="dropdown-toggle glyphicon glyphicon-cog link-grey" data-toggle="dropdown"></a>
    <ul class="dropdown-menu dropdown-menu-right">
        <li>
			<a href="{{ route('customers.merge', ['id' => $customer->id]) }}">{{ __('Merge') }}</a>
        </li>
        {!! safe_raw_html($profile_menu) !!}
    </ul>
</div>