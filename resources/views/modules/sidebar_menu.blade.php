<div class="sidebar-title">
    {{ __('Modules') }}
</div>
<ul class="sidebar-menu">
	@if (count($installed_modules))
    	<li><a href="#installed"><i class="glyphicon glyphicon-saved"></i> {{ __('Installed Modules') }}</a></li>
    @endif
    
    <li><a href="#directory"><i class="glyphicon glyphicon-briefcase"></i> {{ __('Modules Directory') }}</a></li>
</ul>