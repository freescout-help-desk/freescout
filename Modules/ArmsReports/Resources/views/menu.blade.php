{{-- Route::has guard: a stale route cache without the module's routes must
     degrade to a missing menu item, not a layout-breaking exception. --}}
@if (Route::has('armsreports.kpis'))
<li class="dropdown {{ Route::is('armsreports.*') ? 'active' : '' }}" data-arms-reports-dropdown data-reports-label="{{ __('Reports') }}" data-print-label="{{ __('Print / Save as PDF') }}">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ __('ARMS Reports') }} <span class="caret"></span></a>
    <ul class="dropdown-menu" role="menu">
        <li class="{{ Route::is('armsreports.kpis') ? 'active' : '' }}"><a href="{{ route('armsreports.kpis') }}">{{ __('ARMS KPIs') }}</a></li>
        <li class="{{ Route::is('armsreports.agents') ? 'active' : '' }}"><a href="{{ route('armsreports.agents') }}">{{ __('Agent Performance') }}</a></li>
    </ul>
</li>
@endif
