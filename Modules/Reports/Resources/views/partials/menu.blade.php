<li class="dropdown {{ \App\Misc\Helper::menuSelectedHtml('reports') }}">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
        {{ __('Reports') }} <span class="caret"></span>
    </a>

    <ul class="dropdown-menu">
        <li class="{{ \App\Misc\Helper::menuSelectedHtml('reports.conversations') }}"><a href="{{ route('reports.conversations') }}">{{ __('Conversations Report') }}</a></li>
        <li class="{{ \App\Misc\Helper::menuSelectedHtml('reports.productivity') }}"><a href="{{ route('reports.productivity') }}">{{ __('Productivity Report') }}</a></li>
        @if (\Module::isActive('satratings'))
        	<li class="{{ \App\Misc\Helper::menuSelectedHtml('reports.satisfaction') }}"><a href="{{ route('reports.satisfaction') }}">{{ __('Satisfaction Report') }}</a></li>
        @endif
        @if (\Module::isActive('timetracking'))
            <li class="{{ \App\Misc\Helper::menuSelectedHtml('reports.time') }}"><a href="{{ route('reports.time') }}">{{ __('Time Tracking Report') }}</a></li>
        @endif
    </ul>
</li>