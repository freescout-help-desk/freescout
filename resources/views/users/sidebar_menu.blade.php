<ul class="sidebar-menu">
    <li @if (Route::currentRouteName() == 'user.profile')class="active"@endif><a href="{{ route('user.profile', ['id'=>$user->id]) }}"><i class="glyphicon glyphicon-user"></i> {{ __('Profile') }}</a></li>
    <li @if (Route::currentRouteName() == 'user_permissions')class="active"@endif><a href="#"><i class="glyphicon glyphicon-ok"></i> {{ __('Permissions') }} (todo)</a></li>
    <li @if (Route::currentRouteName() == 'user_notifications')class="active"@endif><a href="#"><i class="glyphicon glyphicon-bell"></i> {{ __('Notifications') }} (todo)</a></li>
    <li @if (Route::currentRouteName() == 'user_autobcc')class="active"@endif><a href="#"><i class="glyphicon glyphicon-duplicate"></i> {{ __('Auto Bcc') }} (todo)</a></li>
    <li @if (Route::currentRouteName() == 'user_myapps')class="active"@endif><a href="#"><i class="glyphicon glyphicon-gift"></i> {{ __('My Apps') }} (todo)</a></li>
</ul>