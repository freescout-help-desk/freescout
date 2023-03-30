<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
	<head>
	    <meta charset="utf-8">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">

	    <!-- CSRF Token -->
	    <meta name="csrf-token" content="{{ csrf_token() }}">
	    
	    <meta name="robots" content="noindex, nofollow">
	    <title>{{ __('Help') }}</title>
	    
	    @php
	        try {
	    @endphp
	    {!! Minify::stylesheet(\Eventy::filter('stylesheets', array('/css/bootstrap.css', \Module::getPublicPath(KB_MODULE).'/css/widget_form.css'))) !!}
	    @php
	        } catch (\Exception $e) {
	            // Try...catch is needed to catch errors when activating a module and public symlink not created for module.
	            \Helper::logException($e);
	        }
	    @endphp
	    
	</head>
    <body @yield('body_attrs')>
    	<div id="kb-header" @if (Request::get('color')) style="background-color: {{ Request::get('color') }} @endif">
    		@yield('title')
    		<div id="kb-minimize">
    			<svg width="16" height="16" viewBox="0 0 16 16"><path stroke="white" stroke-linecap="round" stroke-width="2" d="M3 8h10"></path></svg>
    		</div>
	    </div>

        <div id="kb-content">
            @yield('content')
        </div>

	    {{-- Scripts --}}
	    @php
	        try {
	    @endphp
	    {!! Minify::javascript(\Eventy::filter('kb.widget_form.javascripts', ['/js/jquery.js', '/js/bootstrap.js', '/js/lang.js', '/storage/js/vars.js', '/js/laroute.js', \Module::getPublicPath(KB_MODULE).'/js/widget_form.js', '/js/main.js'])) !!}
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
