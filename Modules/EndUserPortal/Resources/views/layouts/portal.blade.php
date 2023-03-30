<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
	<head>
	    <meta charset="utf-8">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">

	    <!-- CSRF Token -->
	    <meta name="csrf-token" content="{{ csrf_token() }}">

	    <meta name="robots" content="noindex, nofollow">

	    <title>@yield('title') - {{ $mailbox->name }}</title>
	    
	    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
	    <link rel="shortcut icon" type="image/x-icon" href="@filter('layout.favicon', URL::asset('favicon.ico'))">
	    <link rel="manifest" href="{{ asset('site.webmanifest') }}" crossorigin="use-credentials">
	    <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#5bbad5">
	    <meta name="msapplication-TileColor" content="#da532c">
	    <meta name="theme-color" content="#ffffff">
	    @action('layout.head')
	    @php
	        try {
	    @endphp
	    {!! Minify::stylesheet(\Eventy::filter('stylesheets', array('/css/fonts.css', '/css/bootstrap.css', '/css/style.css', \Module::getPublicPath(EUP_MODULE).'/css/style.css'))) !!}
	    @php
	        } catch (\Exception $e) {
	            // Try...catch is needed to catch errors when activating a module and public symlink not created for module.
	            \Helper::logException($e);
	        }
	    @endphp
	    
	    @yield('stylesheets')

	    @yield('eup_stylesheets')
	</head>
    <body @yield('body_attrs')>
    	<div id="app">
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
	                    <a class="navbar-brand navbar-brand-with-text" href="{{ \EndUserPortal::urlHome() }}" title="{{ __('Tickets') }}">
	                    	@if (Eventy::filter('layout.header_logo', ''))
	                    		<img src="@filter('layout.header_logo', '')" height="100%" />
	                    	@endif
	                        <span>{{ $mailbox->name }}</span>
	                    </a>
	                </div>

	                <div class="collapse navbar-collapse" id="app-navbar-collapse">
	                    <!-- Left Side Of Navbar -->
	                    <ul class="nav navbar-nav navbar-right">
	                    	<li class="{{ \App\Misc\Helper::menuSelectedHtml('enduserportal.submit') }}"><a href="{{ route('enduserportal.submit', ['id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}">{{ \EndUserPortal::getMailboxParam($mailbox, 'text_submit') }}</a></li>
	                    	<li class="{{ \App\Misc\Helper::menuSelectedHtml('enduserportal.tickets') }}"><a href="{{ route('enduserportal.tickets', ['id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}">{{ __('My Tickets') }}</a></li>
	                    	@if (!EndUserPortal::authCustomer())
	                    		<li><a href="{{ route('enduserportal.login', ['id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}"><i class="glyphicon glyphicon-user"></i> {{ __('Log In') }}</a></li>
	                    	@else
								<li class="dropdown">

                                    <a href="#" class="dropdown-toggle dropdown-toggle-icon dropdown-toggle-account" data-toggle="dropdown">
                                    	<i class="glyphicon glyphicon-user"></i> <span class="nav-user">{{ EndUserPortal::authCustomer()->getMainEmail() }}</span> <span class="caret"></span>
                                    </a>

                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{{ route('logout') }}"
                                                onclick="event.preventDefault();
                                                         document.getElementById('logout-form').submit();">
                                                {{ __('Log Out') }}
                                            </a>

                                            <form id="logout-form" action="{{ route('enduserportal.logout', ['id' => \EndUserPortal::encodeMailboxId($mailbox->id)]) }}" method="POST" style="display: none;">
                                                {{ csrf_field() }}
                                            </form>
                                        </li>
                                    </ul>
                                </li>
	                    	@endif
	                    </ul>
	                </div>
	            </div>
	        </nav>
	        <div class="content @yield('content_class')">
	            @yield('content')
	        </div>
			<div class="footer">
                {!! strtr($mailbox->meta['eup']['footer'] ?? \EndUserPortal::getDefaultPortalSettings()['footer'], ['{%year%}' => date('Y'), '{%mailbox.name%}' => $mailbox->name]) !!}
            </div>
	    </div>

	    @action('layout.body_bottom')

	    {{-- Scripts --}}
	    @php
	        try {
	    @endphp
	    {!! Minify::javascript(\Eventy::filter('eup.javascripts', ['/js/jquery.js', '/js/bootstrap.js', '/js/lang.js', '/storage/js/vars.js', '/js/laroute.js', '/js/parsley/parsley.min.js', '/js/parsley/i18n/'.strtolower(Config::get('app.locale')).'.js', \Module::getPublicPath(EUP_MODULE).'/js/laroute.js', \Module::getPublicPath(EUP_MODULE).'/js/main.js', '/js/main.js'])) !!}
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
	    @yield('eup_javascripts')
	    <script type="text/javascript">
	        @yield('eup_javascript')
	    </script>
    </body>
</html>
