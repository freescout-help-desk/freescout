<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@if ($__env->yieldContent('title_full'))@yield('title_full') @elseif ($__env->yieldContent('title'))@yield('title') - {{ config('app.name', 'FreeScout') }} @else{{ config('app.name', 'FreeScout') }}@endif</title>
    
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    {{--<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">--}}
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    {{-- Styles --}}
    {{-- Conversation page must open immediately, so we are loading scripts present on conversation page --}}
    {{-- style.css must be the last to able to redefine styles --}}
    @php
        try {
    @endphp
    {!! Minify::stylesheet(\Eventy::filter('stylesheets', array('/css/fonts.css', '/css/bootstrap.css', '/css/select2/select2.min.css', '/js/featherlight/featherlight.min.css', '/js/featherlight/featherlight.gallery.min.css', '/css/magic-check.css', '/css/style.css'))) !!}
    @php
        } catch (\Exception $e) {
            // Try...catch is needed to catch errors when activating a module and public symlink not created for module.
            \Helper::logException($e);
        }
    @endphp
    
    @yield('stylesheets')
</head>
<body class="@if (!Auth::user()) user-is-guest @endif @if (Auth::user() && Auth::user()->isAdmin()) user-is-admin @endif @yield('body_class')" @yield('body_attrs') @if (Auth::user()) data-auth_user_id="{{ Auth::user()->id }}" @endif>
    <div id="app">

        @if (Auth::user())

            <nav class="navbar navbar-default navbar-static-top">
                <div class="container">
                    <div class="navbar-header">

                        <!-- Collapsed Hamburger -->
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse" aria-expanded="false">
                            <span class="sr-only">{{ __('Toggle Navigation') }}</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>

                        <!-- Branding Image -->
                        @if (\Helper::isInApp() && \Helper::isRoute('conversations.view'))
                            <a class="navbar-brand" href="javascript: goBack(); void(0);" title="{{ __('Back') }}">
                                <i class="glyphicon glyphicon-arrow-left"></i>
                            </a>
                        @else
                            <a class="navbar-brand" href="{{ url('/') }}" title="{{ __('Dashboard') }}">
                                <img src="{{ asset('img/logo-brand.png') }}" />
                                {{-- config('app.name', 'FreeScout') --}}
                            </a>
                        @endif
                    </div>

                    <div class="collapse navbar-collapse" id="app-navbar-collapse">
                        <!-- Left Side Of Navbar -->
                        <ul class="nav navbar-nav">
                            @php
                                $cache_mailboxes = false;
                                if (\Helper::isRoute('conversations.view')) {
                                    $cache_mailboxes = true;
                                }
                                $mailboxes = Auth::user()->mailboxesCanView($cache_mailboxes);
                            @endphp
                            @if (count($mailboxes) == 1)
                                <li class="{{ \App\Misc\Helper::menuSelectedHtml('mailbox') }}"><a href="{{ route('mailboxes.view', ['id'=>$mailboxes[0]->id]) }}">{{ __('Mailbox') }}</a></li>
                            @elseif (count($mailboxes) > 1) 
                                <li class="dropdown {{ \App\Misc\Helper::menuSelectedHtml('mailbox') }}">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
                                        {{ __('Mailbox') }} <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        @foreach ($mailboxes as $mailbox_item)
                                            <li @if ($mailbox_item->id == app('request')->id)class="active"@endif><a href="{{ route('mailboxes.view', ['id' => $mailbox_item->id]) }}">{{ $mailbox_item->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                            @if (Auth::user()->isAdmin() || Auth::user()->can('viewMailboxMenu', Auth::user()))
                                <li class="dropdown {{ \App\Misc\Helper::menuSelectedHtml('manage') }}">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
                                        {{ __('Manage') }} <span class="caret"></span>
                                    </a>

                                    <ul class="dropdown-menu">
                                        @if (Auth::user()->isAdmin())
                                            <li class="{{ \App\Misc\Helper::menuSelectedHtml('settings') }}"><a href="{{ route('settings') }}">{{ __('Settings') }}</a></li>
                                        @endif
                                        @if (Auth::user()->can('viewMailboxMenu', Auth::user()))
                                            <li class="{{ \App\Misc\Helper::menuSelectedHtml('mailboxes') }}"><a href="{{ route('mailboxes') }}">{{ __('Mailboxes') }}</a></li>
                                        @endif
                                        @if (Auth::user()->isAdmin())
                                            <li class="{{ \App\Misc\Helper::menuSelectedHtml('users') }}"><a href="{{ route('users') }}">{{ __('Users') }}</a></li>
                                            <li class="{{ \App\Misc\Helper::menuSelectedHtml('modules') }}"><a href="{{ route('modules') }}">{{ __('Modules') }}</a></li>
                                            <li class=""><a href="{{ asset('translations') }}">{{ __('Translate') }}</a></li>
                                            <li class="{{ \App\Misc\Helper::menuSelectedHtml('logs') }}"><a href="{{ route('logs') }}">{{ __('Logs') }}</a></li>
                                            <li class="{{ \App\Misc\Helper::menuSelectedHtml('system') }}"><a href="{{ route('system') }}">{{ __('System') }}</a></li>
                                        @endif
                                    </ul>
                                </li>
                            @endif
                        </ul>

                        <!-- Right Side Of Navbar -->
                        <ul class="nav navbar-nav navbar-right">
                            <!-- Authentication Links -->
                            @guest
                                &nbsp;
                            @else
                                <li class="dropdown web-notifications">
                                    @php
                                        $web_notifications_info = Auth::user()->getWebsiteNotificationsInfo();
                                    @endphp
                                    <a href="#" class="dropdown-toggle dropdown-toggle-icon @if ($web_notifications_info['unread_count']) @if ($web_notifications_info['unread_count']) has-unread @endif @endif" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre title="{{ __('Notifications') }}">
                                        <i class="glyphicon glyphicon-bell"></i>
                                    </a>

                                    <ul class="dropdown-menu">
                                        <li>
                                            <div class="web-notifications-header">
                                                <h1>
                                                    {{ __('Notifications') }}
                                                    <small class="web-notifications-count  @if (!(int)$web_notifications_info['unread_count']) hidden @endif" title="{{ __('Unread Notifications') }}" data-toggle="tooltip">@if ($web_notifications_info['unread_count']){{ $web_notifications_info['unread_count'] }}@endif</small>
                                                </h1>
                                                <a href="#" class="web-notifications-mark-read @if (!(int)$web_notifications_info['unread_count']) hidden @endif" data-loading-text="{{ __('Processing') }}…">
                                                    {{ __('Mark all as read') }}
                                                </a>
                                            </div>
                                            <ul class="web-notifications-list">
                                                @if (count($web_notifications_info['data']))
                                                    @if (!empty($web_notifications_info['html']))
                                                        {!! $web_notifications_info['html'] !!}
                                                    @else
                                                        @include('users/partials/web_notifications', ['web_notifications_info_data' => $web_notifications_info['data']])
                                                    @endif

                                                    @if ($web_notifications_info['notifications']->hasMorePages())
                                                        <li class="web-notification-more">
                                                            <button class="btn btn-link btn-block link-dark" data-loading-text="{{ __('Loading') }}…">
                                                                {{ __('Load more') }}
                                                            </button>
                                                        </li>
                                                    @endif
                                                @else
                                                    <div class="text-center margin-top-40 margin-bottom-40">
                                                        <i class="glyphicon glyphicon-bullhorn icon-large"></i>
                                                        <p class="block-help text-large">
                                                            {{ __('Notifications will start showing up here soon') }}
                                                        </p>
                                                        <a href="{{ route('users.notifications', ['id' => Auth::user()->id]) }}">{{ __('Update your notification settings') }}</a>
                                                    </div>
                                                @endif
                                            </ul>
                                        </li>
                                        
                                    </ul>
                                </li>
                                                                

                                <li class="dropdown">

                                    <a href="#" class="dropdown-toggle dropdown-toggle-icon dropdown-toggle-account" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre title="{{ __('Account') }}">
                                        <span class="photo-sm">@include('partials/person_photo', ['person' => Auth::user()])</span>&nbsp;<span class="nav-user">{{ Auth::user()->first_name }}</span> <span class="caret"></span>
                                    </a>

                                    <ul class="dropdown-menu">
                                        <li><a href="{{ route('users.profile', ['id'=>Auth::user()->id]) }}">{{ __('Your Profile') }}</a></li>
                                        <li class="divider"></li>
                                        <li>
                                            <a href="{{ route('logout') }}"
                                                onclick="event.preventDefault();
                                                         document.getElementById('logout-form').submit();">
                                                {{ __('Log Out') }}
                                            </a>

                                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                                {{ csrf_field() }}
                                            </form>
                                        </li>
                                        <li class="divider hidden in-app-switcher"></li>
                                        <li>
                                            <a href="javascript:switchHelpdeskUrl();void(0);" class="hidden in-app-switcher">{{ __('Switch Helpdesk URL' ) }}</a>
                                        </li>
                                    </ul>
                                </li>

                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle dropdown-toggle-icon" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre title="{{ __('Search') }}">
                                        <i class="glyphicon glyphicon-search"></i>
                                    </a>

                                    <ul class="dropdown-menu">
                                        <li>
                                            <form class="form-inline form-nav-search" role="form" action="{{ route('conversations.search') }}" target="_blank">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="q">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default" type="submit">{{ __('Search') }}</button>
                                                    </span>
                                                </div>
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>
        @endif

        @if ($__env->yieldContent('sidebar'))
            <div class="layout-2col">
                <div class="sidebar-2col">
                    @yield('sidebar')
                </div>
                <div class="content-2col">
                    @yield('content')
                </div>
            </div>
        @else
            <div class="content">
                @yield('content')
            </div>
        @endif

        @if (!in_array(Route::currentRouteName(), array('mailboxes.view')))
            <div class="footer">
                &copy; {{ date('Y') }} <a href="{{ config('app.freescout_url') }}" target="blank">{{ \Config::get('app.name') }}</a> — {{ __('Free open source help desk &amp; shared mailbox' ) }}
                    @if (!Auth::user())
                        <a href="javascript:switchHelpdeskUrl();void(0);" class="hidden in-app-switcher"><br/>{{ __('Switch Helpdesk URL' ) }}</a>
                    @endif
                    {{-- Show version to admin only --}}
                    @if (Auth::user() && Auth::user()->isAdmin())
                        <br/>
                        <a href="{{ route('system') }}">{{ config('app.version') }}</a>
                    @endif
            </div>
        @endif
    </div>

    <div id="loader-main"></div>

    @include('partials/floating_flash_messages')

    @yield('body_bottom')
    @action('layout.body_bottom')

    {{-- Scripts --}}
    @php
        try {
    @endphp
    {!! Minify::javascript(\Eventy::filter('javascripts', array('/js/jquery.js', '/js/bootstrap.js', '/js/lang.js', '/storage/js/vars.js', '/js/laroute.js', '/js/parsley/parsley.min.js', '/js/parsley/i18n/'.strtolower(Config::get('app.locale')).'.js', '/js/select2/select2.full.min.js', '/js/polycast/polycast.js', '/js/push/push.min.js', '/js/featherlight/featherlight.min.js', '/js/featherlight/featherlight.gallery.min.js', '/js/taphold.js', '/js/main.js'))) !!}
    @php
        } catch (\Exception $e) {
            // To prevent 500 errors on update.
            // Also catches errors when activating a module and public symlink not created for module.
            if (strstr($e->getMessage(), 'vars.js')) {
                \Artisan::call('freescout:generate-vars');
            }
            \Helper::logException($e);
        }
    @endphp
    @yield('javascripts')
    <script type="text/javascript">
        @if (\Helper::isInApp()) 
            @if (Auth::user())
                fs_in_app_data['token'] = '{{ Auth::user()->getAuthToken() }}';
            @else
                fs_in_app_data['token'] = '';
            @endif
        @endif
        @yield('javascript')
        @action('javascript')
    </script>
</body>
</html>
