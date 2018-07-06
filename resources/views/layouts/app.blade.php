<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@if ($__env->yieldContent('title_full')) @yield('title_full') @elseif ($__env->yieldContent('title')) @yield('title') - {{ config('app.name', 'FreeScout') }} @else {{ config('app.name', 'FreeScout') }} @endif</title>

    <!-- Styles -->
    {!! Minify::stylesheet(array('/css/fonts.css', '/css/bootstrap.css', '/css/style.css')) !!}
    @yield('stylesheets')
</head>
<body>
    <div id="app">

        @if (!in_array(Route::currentRouteName(), array('login', 'register')))

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
                        <a class="navbar-brand @if (Route::currentRouteName() == 'dashboard')active @endif" href="{{ url('/') }}" title="{{ __('Dashboard') }}">
                            <img src="/img/logo-brand.png" />
                            {{-- config('app.name', 'FreeScout') --}}
                        </a>
                    </div>

                    <div class="collapse navbar-collapse" id="app-navbar-collapse">
                        <!-- Left Side Of Navbar -->
                        <ul class="nav navbar-nav">
                            @php
                                $mailboxes = Auth::user()->mailboxesHasAccess();
                            @endphp
                            @if (count($mailboxes) == 1)
                                <li><a href="{{ route('mailboxes.view', ['id'=>$mailboxes[0]->id]) }}" @if (Route::currentRouteName() == 'mailboxes.view')class="active"@endif>{{ __('Mailbox') }}</a></li>
                            @else
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
                                        {{ __('Mailbox') }} <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        @foreach ($mailboxes as $mailbox_item)
                                            <li @if ($mailbox_item->id == app('request')->input('id'))class="active"@endif><a href="{{ route('mailboxes.view', ['id' => app('request')->input('id')]) }}">{{ $mailbox_item->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                            @if (Auth::user()->isAdmin())
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
                                        {{ __('Docs') }} <span class="caret"></span>
                                    </a>

                                    <ul class="dropdown-menu">
                                        <li><a href="#">{{ __('New Site...') }} (todo)</a></li>
                                    </ul>
                                </li>
                            @endif
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

                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
                                    {{ __('Manage') }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu">
                                    @if (Auth::user()->isAdmin())
                                        <li><a href="#">{{ __('Apps') }} (todo)</a></li>
                                        <li><a href="#">{{ __('Plugins') }} (todo)</a></li>
                                        <li><a href="#">{{ __('Company') }} (todo)</a></li>
                                        <li><a href="#">{{ __('Docs') }} (todo)</a></li>
                                        <li><a href="{{ route('mailboxes') }}">{{ __('Mailboxes') }}</a></li>
                                    @endif
                                    <li><a href="#">{{ __('Tags') }} (todo)</a></li>
                                    @if (Auth::user()->isAdmin())
                                        <li><a href="#">{{ __('Teams') }} (todo)</a></li>
                                        <li><a href="{{ route('users') }}">{{ __('Users') }}</a></li>
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
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle dropdown-toggle-icon" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre title="{{ __('Account') }}">
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
                &copy; {{ date('Y') }}, <a href="{{ config('app.freescout_url') }}" target="blank">FreeScout</a> — Free open source help desk &amp; shared mailbox<br/>v{{ config('app.version') }}
            </div>
        @endif
    </div>

    <!-- Scripts -->
    {!! Minify::javascript(array('/js/jquery.js', '/js/bootstrap.js', '/js/main.js')) !!}
    @yield('javascripts')
    @if ($__env->yieldContent('javascript'))
        <script type="text/javascript">
            @yield('javascript')
        </script>
    @endif
</body>
</html>
