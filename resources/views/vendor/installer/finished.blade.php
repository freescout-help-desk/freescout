@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.final.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-flag-checkered fa-fw" aria-hidden="true"></i>
    {{ trans('installer_messages.final.title') }}
@endsection

@section('container')

	<h4 style="margin-top:0">Success!</h4>
	<p>
		{{ \Config::get('app.name') }} has been successfully installed, now you need to set up a cron task:
	</p>
	<textarea rows="2" readonly="readonly" style="font-size:12px;">* * * * * php {{ base_path() }}/artisan schedule:run >> /dev/null 2>&1</textarea>
	<p>
		<small>If you don't know how to configure cron jobs, contact your hosting provider. On some shared hostings you may need to specify full path to the PHP executable (for example, <code>/usr/local/bin/php-7.0</code>)</small>
	</p>
	
	@if ($dbMessage && !empty($dbMessage['status']) && $dbMessage['status'] == 'error' && !empty($dbMessage['message']))
		<h4>Database Migration Error</h4>
		<p>Error occured migrating database:</p>
		<p>
			<strong class="has-error">{{ $dbMessage['message'] }}</strong>
		</p>

		<p>
			Please configure database access in <code>.env</code> file and run the following console commands:
		</p>
		<pre><code>php artisan freescout:clear-cache
php artisan migrate</code></pre>
	@endif

	<h4>Admin Credentials</h4>
	@php
		$admin = \App\User::where('role', \App\User::ROLE_ADMIN)->orderBy('id', 'desc')->first();
		if ($admin) {
			$admin_email = $admin->email;
		}
	@endphp
	<p>
		<strong>Email:</strong> <span style="color: #93a1af;">{{ $admin_email }}</span><br/>
		<strong>Password:</strong> <span style="color: #93a1af;">your chosen password</span>
	</p>

    <div class="buttons">
        <a href="{{ url('/') }}" class="button" target="_blank">Login</a>
    </div>


    <h4>Installation Logs</h4>

	@if ($dbMessage && !empty($dbMessage['dbOutputLog']))
		<p>Database Migration:</p>
		<pre><code>{{ $dbMessage['dbOutputLog'] }}</code></pre>
	@endif

	<p>{{ trans('installer_messages.final.console') }}</p>
	<pre><code>{{ $finalMessages }}</code></pre>

	<p>{{ trans('installer_messages.final.log') }}</p>
	<pre><code>{{ $finalStatusMessage }}</code></pre>

	<p>{{ trans('installer_messages.final.env') }}</p>
	<pre><code>{{ $finalEnvFile }}</code></pre>

@endsection
