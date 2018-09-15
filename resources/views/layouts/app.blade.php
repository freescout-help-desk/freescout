<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@if ($__env->yieldContent('title_full')) @yield('title_full') @elseif ($__env->yieldContent('title')) @yield('title') - {{ config('app.name', 'FreeScout') }} @else {{ config('app.name', 'FreeScout') }} @endif</title>

    {{-- Styles --}}
    {{-- Conversation page must open immediately, so we are loading scripts present on conversation page --}}
    {{-- style.css must be the last to able to redefine styles --}}
    {!! Minify::stylesheet(array('/css/fonts.css', '/css/bootstrap.css', '/css/select2/select2.min.css', '/js/featherlight/featherlight.min.css', '/js/featherlight/featherlight.gallery.min.css', '/css/style.css')) !!}
    @yield('stylesheets')
</head>
<body class="@if (Auth::user() && Auth::user()->isAdmin()) user-is-admin @endif @yield('body_class')" @yield('body_attrs') @if (Auth::user()) data-auth_user_id="{{ Auth::user()->id }}" @endif>
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
                        <a class="navbar-brand {{ \App\Misc\Helper::menuSelectedHtml('dashboard') }}" href="{{ url('/') }}" title="{{ __('Dashboard') }}">
                            <img src="/img/logo-brand.png" />
                            {{-- config('app.name', 'FreeScout') --}}
                        </a>
                    </div>

                    <div class="collapse navbar-collapse" id="app-navbar-collapse">
                        <!-- Left Side Of Navbar -->
                        <ul class="nav navbar-nav">
                            @php
                                $mailboxes = Auth::user()->mailboxesCanView();
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
                            {{--@if (Auth::user()->isAdmin())
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
                                        {{ __('Docs') }} <span class="caret"></span>
                                    </a>

                                    <ul class="dropdown-menu">
                                        <li><a href="#">{{ __('New Site...') }} (todo)</a></li>
                                    </ul>
                                </li>
                            
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
                                        {{ __('Reports') }} <span class="caret"></span>
                                    </a>

                                    <ul class="dropdown-menu">
                                        <li><a href="#">{{ __('Conversations') }} (todo)</a></li>
                                        <li><a href="#">{{ __('Productivity') }} (todo)</a></li>
                                        <li><a href="#">{{ __('Team') }} (todo)</a></li>
                                        <li><a href="#">{{ __('Happiness') }} (todo)</a></li>
                                        <li><a href="#">{{ __('Docs') }}  (todo)</a></li>
                                    </ul>
                                </li>
                            @endif--}}
                            <li class="dropdown {{ \App\Misc\Helper::menuSelectedHtml('manage') }}">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
                                    {{ __('Manage') }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu">
                                    @if (Auth::user()->isAdmin())
                                        {{--<li><a href="#">{{ __('Apps') }} (todo)</a></li>--}}
                                        <li class="{{ \App\Misc\Helper::menuSelectedHtml('settings') }}"><a href="{{ route('settings') }}">{{ __('Settings') }}</a></li>
                                        {{--<li><a href="#">{{ __('Docs') }} (todo)</a></li>--}}
                                        <li class="{{ \App\Misc\Helper::menuSelectedHtml('mailboxes') }}"><a href="{{ route('mailboxes') }}">{{ __('Mailboxes') }}</a></li>
                                    @endif
                                    <li class="{{ \App\Misc\Helper::menuSelectedHtml('tags') }}"><a href="#">{{ __('Tags') }} (todo)</a></li>
                                    @if (Auth::user()->isAdmin())
                                        {{--<li><a href="#">{{ __('Teams') }} (todo)</a></li>--}}
                                        <li class="{{ \App\Misc\Helper::menuSelectedHtml('users') }}"><a href="{{ route('users') }}">{{ __('Users') }}</a></li>
                                        <li class="{{ \App\Misc\Helper::menuSelectedHtml('plugins') }}"><a href="#">{{ __('Plugins') }} (todo)</a></li>
                                        <li class="{{ \App\Misc\Helper::menuSelectedHtml('logs') }}"><a href="{{ route('logs') }}">{{ __('Logs') }}</a></li>
                                        <li class="{{ \App\Misc\Helper::menuSelectedHtml('system') }}"><a href="{{ route('system') }}">{{ __('System') }}</a></li>
                                    @endif
                                </ul>
                            </li>
                        </ul>

                        <!-- Right Side Of Navbar -->
                        <ul class="nav navbar-nav navbar-right">
                            <!-- Authentication Links -->
                            @guest
                                {{-- <li><a href="{{ route('login') }}">{{ __('Login') }}</a></li> --}}&nbsp;
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
                                                    @include('users/partials/web_notifications', ['web_notifications_info_data' => $web_notifications_info['data']])

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
                                        <i class="glyphicon glyphicon-user"></i> <span class="nav-user">{{ Auth::user()->first_name }}</span> <span class="caret"></span>
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
                    {{-- Hide version from hackers --}}
                    @if (Auth::user())
                        <br/>{{ config('app.version') }}
                    @endif
            </div>
        @endif
    </div>

    <div id="loader-main"></div>

    @include('partials/floating_flash_messages')

    @yield('body_bottom')

    {{-- Scripts --}}
    {!! Minify::javascript(array('/js/jquery.js', '/js/bootstrap.js', '/js/laroute.js', '/js/lang.js', '/js/vars.js', '/js/parsley/parsley.min.js', '/js/parsley/i18n/'.Config::get('app.locale').'.js', '/js/select2/select2.full.min.js', '/js/polycast/polycast.js', '/js/push/push.min.js', '/js/featherlight/featherlight.min.js', '/js/featherlight/featherlight.gallery.min.js', '/js/main.js')) !!}
    @yield('javascripts')
    @if ($__env->yieldContent('javascript'))
        <script type="text/javascript">
            @yield('javascript')
        </script>
    @endif
</body>
</html>
