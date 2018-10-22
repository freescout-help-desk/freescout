@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.final.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-flag-checkered fa-fw" aria-hidden="true"></i>
    {{ trans('installer_messages.final.title') }}
@endsection

@section('container')

	<h4 style="margin-top:0">Final Step</h4>

	<p>
		Now you need to set up a cron task (If you don't know how to do it, contact your hosting provider):
	</p>
	<textarea rows="1" readonly="readonly" style="font-size:12px;">* * * * * php /home/user/artisan schedule:run >> /dev/null 2>&1</textarea>
	<p>
		Make sure to replace <code>/home/user</code> with the path your installation. On some shared hostings you may need to specify full path to the PHP executable (for example, <code>/usr/local/bin/php-7.0</code>)
	</p>
	

	<h4>Admin Credentials</h4>

	<p>
		<strong>Email:</strong> {{ old('admin_email') }}<br/>
		<strong>Password:</strong> {{ old('admin_password') }}
	</p>

    <div class="buttons">
        <a href="{{ url('/') }}" class="button" target="_blank">Login</a>
    </div>


    <h4>Installation Logs</h4>

	@if(session('message')['dbOutputLog'])
		<p><strong><small>{{ trans('installer_messages.final.migration') }}</small></strong></p>
		<pre><code>{{ session('message')['dbOutputLog'] }}</code></pre>
	@endif

	<p><strong><small>{{ trans('installer_messages.final.console') }}</small></strong></p>
	<pre><code>{{ $finalMessages }}</code></pre>

	<p><strong><small>{{ trans('installer_messages.final.log') }}</small></strong></p>
	<pre><code>{{ $finalStatusMessage }}</code></pre>

	<p><strong><small>{{ trans('installer_messages.final.env') }}</small></strong></p>
	<pre><code>{{ $finalEnvFile }}</code></pre>

@endsection
