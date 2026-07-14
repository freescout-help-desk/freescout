<li class="dropdown {{ Route::is('armsreports.*') ? 'active' : '' }}">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ __('ARMS Reports') }} <span class="caret"></span></a>
    <ul class="dropdown-menu" role="menu">
        <li class="{{ Route::is('armsreports.kpis') ? 'active' : '' }}"><a href="{{ route('armsreports.kpis') }}">{{ __('ARMS KPIs') }}</a></li>
        <li class="{{ Route::is('armsreports.agents') ? 'active' : '' }}"><a href="{{ route('armsreports.agents') }}">{{ __('Agent Performance') }}</a></li>
    </ul>
</li>
