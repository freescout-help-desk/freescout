@php
    $profile_menu = \Eventy::filter('customer.profile_menu', '', $customer)
@endphp
@if ($profile_menu)
    <div class="dropdown customer-profile-menu">
        <a href="javascript:void(0)" class="dropdown-toggle glyphicon glyphicon-cog link-grey" data-toggle="dropdown"></a>
        <ul class="dropdown-menu dropdown-menu-right">
            {!! $profile_menu !!}
        </ul>
    </div>
@endif