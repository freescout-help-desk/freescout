<div class="sidebar-title">
    {{ __('Modules') }}
</div>
<ul class="sidebar-menu">
	@if (count($installed_modules))
    	<li><a href="#installed"><i class="glyphicon glyphicon-saved"></i> {{ __('Installed Modules') }}@if (count($installed_modules)) <small>({{ count($installed_modules) }})</small>@endif</a></li>
    @endif
    <li><a href="#directory"><i class="glyphicon glyphicon-briefcase"></i> {{ __('Modules Directory') }}@if (count($modules_directory)) <small>({{ count($modules_directory) }})</small>@endif</a></li>
    <li><a href="#third-party"><i class="glyphicon glyphicon-shopping-cart"></i> {{ __('Marketplace') }}@if (count($third_party_modules)) <small>({{ count($third_party_modules) }})</small>@endif</a></li>
</ul>