<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
	<head>
	    <meta charset="utf-8">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">

	    <!-- CSRF Token -->
	    <meta name="csrf-token" content="{{ csrf_token() }}">

	    <meta name="robots" content="noindex, nofollow">

	    <title>Overview</title>

	    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	    {{--<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">--}}
	    <link rel="manifest" href="/site.webmanifest">
	    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
	    <meta name="msapplication-TileColor" content="#da532c">
	    <meta name="theme-color" content="#ffffff">
	    @action('layout.head')
	    @php
	        try {
	    @endphp
	    {!! Minify::stylesheet(\Eventy::filter('stylesheets', array('/css/fonts.css', '/css/bootstrap.css', '/css/style.css', \Module::getPublicPath(KB_MODULE).'/css/style.css'))) !!}
	    @php
	        } catch (\Exception $e) {
	            // Try...catch is needed to catch errors when activating a module and public symlink not created for module.
	            \Helper::logException($e);
	        }
	    @endphp

	    @yield('stylesheets')
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
	                    <a class="navbar-brand navbar-brand-with-text" href="{{ route('knowledgebase.frontend.index', []) }}">
	                    	@if (Eventy::filter('layout.header_logo', ''))
	                    		<img src="@filter('layout.header_logo', '')" height="100%" />
	                    	@endif
	                        <span>Overview</span>
	                    </a>
	                </div>

	                <div class="collapse navbar-collapse" id="app-navbar-collapse">
	                    <!-- Left Side Of Navbar -->
	                    <ul class="nav navbar-nav navbar-right">
	                    	<li class="{{ \App\Misc\Helper::menuSelectedHtml('knowledgebase.frontend.index') }}"><a href="{{ route('knowledgebase.frontend.index', []) }}">{{ __('Home') }}</a></li>
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle dropdown-toggle-icon" data-toggle="dropdown" title="{{ __('Search') }}">
                                    <i class="glyphicon glyphicon-search"></i>
                                </a>

                                
                            </li>
	                    </ul>
	                </div>
	            </div>
	        </nav>
	        <div class="content @yield('content_class')">
	        	<div id="kb-container" class="kb-overview">
	            	@yield('content')
	            </div>
	        </div>
	    </div>

	    @action('layout.body_bottom')

	    {{-- Scripts --}}
	    @php
	        try {
	    @endphp
	    {!! Minify::javascript(\Eventy::filter('kb.javascripts', ['/js/jquery.js', '/js/bootstrap.js', '/js/lang.js', '/storage/js/vars.js', '/js/laroute.js', '/js/parsley/parsley.min.js', '/js/parsley/i18n/'.strtolower(Config::get('app.locale')).'.js', '/js/main.js'])) !!}
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
	    <script type="text/javascript">
	        @yield('kb_javascript')
	    </script>
    </body>
</html>
