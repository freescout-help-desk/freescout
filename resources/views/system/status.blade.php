@extends('layouts.app')

@section('title', __('System Status'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('system/sidebar_menu')
@endsection

@section('content')

<div class="section-heading">
    {{ __('System Status') }}
</div>

<div class="container">

    @action('system.status.before_info_table')

    <h3 id="app">{{ __('Info') }}</h3>

    <table class="table table-dark-header table-bordered table-responsive">
        <tbody>
            <tr id="version">
                <th>{{ __('App Version') }}</th>
                <td class="table-main-col">
                    @if (!\Config::get('app.disable_updating'))
                        @if ($new_version_available)
                            <strong class="text-danger">{{ \Config::get('app.version') }}</strong>
                            <div class="alert alert-danger margin-top-10">
                                {{ __('A new version is available') }}: <strong>{{ $latest_version }}</strong> <a href="{{ config('app.freescout_repo') }}/releases" target="_blank">({{ __('View details') }})</a>
                                <button class="btn btn-default btn-sm update-trigger margin-left-10" data-loading-text="{{ __('Updating') }}…{{ __('This may take several minutes') }}"><small class="glyphicon glyphicon-refresh"></small> {{ __('Update Now') }}</button>
                            </div>
                        @else
                            <strong class="text-success">{{ \Config::get('app.version') }}</strong>
                            &nbsp;&nbsp;
                            <a href="#" class="btn btn-default btn-xs check-updates-trigger" data-loading-text="{{ __('Checking') }}…">{{ __('Check for updates') }}</a>
                            @if ($latest_version_error)
                                <div class="text-danger margin-top">{{ $latest_version_error }}</div>
                            @endif
                        @endif
                    @else
                        <strong class="text-success">{{ \Config::get('app.version') }}</strong>
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('Date & Time') }}</th>
                <td class="table-main-col">{{ App\User::dateFormat(new Illuminate\Support\Carbon(), 'M j, Y H:i', null, true, false) }}</td>
            </tr>
            <tr>
                <th>{{ __('Timezone') }} (.env)</th>
                <td class="table-main-col">{{ \Config::get('app.timezone') }} (GMT{{ date('O') }})</td>
            </tr>
            <tr>
                <th>{{ __('Protocol') }}</th>
                <td class="table-main-col" id="system-app-protocol"></td>
            </tr>
            @if (\Helper::detectCloudFlare())
                @php
                    $cloudflare_is_used = config('app.cloudflare_is_used');
                @endphp
                <tr>
                    <th>Proxy</th>
                    <td class="table-main-col">
                        <div @if (!$cloudflare_is_used) class="alert alert-warning alert-narrow margin-bottom-0" @endif>
                            @if (!$cloudflare_is_used)<i class="glyphicon glyphicon-exclamation-sign"></i> @endif{{ 'CloudFlare' }} (<a href="{{ config('app.freescout_repo') }}/wiki/Installation-Guide#103-cloudflare" target="_blank">{{ __('read more') }}</a>)
                        </div>
                    </td>
                </tr>
            @endif
            {{--<tr>
                <th>{{ __('.env file') }}</th>
                <td class="table-main-col">
                    @if (\File::exists(base_path().DIRECTORY_SEPARATOR.'.env'))
                        {{ 'Exists'}}
                    @else
                        <strong class="text-danger">{{ 'Not found'}}</strong>
                    @endif
                </td>
            </tr>--}}
            <tr>
                <th>DB</th>
                <td class="table-main-col">
                    {{ ucfirst(\DB::connection()->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME)) }} ({{ \DB::connection()->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION) }})
                    @if ($missing_migrations)
                        &nbsp;&nbsp;<a href="{{ route('system.tools') }}" class="btn btn-danger btn-xs">{{ 'Migrate DB' }}</a>
                        <div class="alert alert-danger margin-top-10">
                            @foreach($missing_migrations as $missing_migration)
                                {{ $missing_migration }} <strong class="glyphicon glyphicon-exclamation-sign"></strong><br/>
                            @endforeach
                        </div>
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('Web Server') }}</th>
                <td class="table-main-col">@if (!empty($_SERVER['SERVER_SOFTWARE'])){{ $_SERVER['SERVER_SOFTWARE'] }}@else ? @endif</td>
            </tr>
            <tr>
                <th>{{ __('PHP Version') }}</th>
                <td class="table-main-col">PHP {{ phpversion() }}</td>
            </tr>
            <tr>
                <th>PHP upload_max_filesize / post_max_size</th>
                <td class="table-main-col">{{ ini_get('upload_max_filesize') }} / {{ ini_get('post_max_size') }}</td>
            </tr>
        </tbody>
    </table>

    @action('system.status.after_info_table')

    <h3 id="php">{{ __('PHP Extensions') }}</h3>
    <table class="table table-dark-header table-bordered table-responsive table-narrow">
        <tbody>
            @foreach ($php_extensions as $extension_name => $extension_status)
                <tr>
                    <th>{{ $extension_name }}@if ($extension_name == 'intl' && !$extension_status) {{ __('(optional)') }}@endif</th>
                    <td class="table-main-col">
                        @if ($extension_status)
                            <strong class="text-success">OK</strong>
                        @else
                            <strong class="text-danger">{{ __('Not found') }}</strong>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @action('system.status.after_php_extensions')

    <h3 id="php">{{ __('Functions') }}</h3>
    <table class="table table-dark-header table-bordered table-responsive table-narrow">
        <tbody>
            @foreach ($functions as $functions_name => $functions_status)
                <tr>
                    <th>{{ $functions_name }}</th>
                    <td class="table-main-col">
                        @if ($functions_status)
                            <strong class="text-success">OK</strong>
                        @else
                            <strong class="text-danger">{{ __('Not found') }}</strong>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @action('system.status.after_functions')

    <h3 id="permissions">{{ __('Permissions') }}</h3>
    {!! __('These folders must be writable by web server user (:user).', ['user' => '<strong>'.(function_exists('get_current_user') ? get_current_user() : '').'</strong>']) !!} {{ __('Recommended permissions') }}: <strong>775</strong>
    <table class="table table-dark-header table-bordered table-responsive table-narrow">
        <tbody>
            @foreach ($permissions as $perm_path => $perm)
                <tr>
                    <th>{{ $perm_path }}</th>
                    <td class="table-main-col">
                        @if ($perm_path == 'storage/framework/cache/data/')
                            @if ($non_writable_cache_file)
                                <strong class="text-danger">{{ __('Non-writable files found') }}</strong>
                                <br/>
                                <span class="text-danger">{{ $non_writable_cache_file }}</span>
                                <br/><br/>
                                {{ __('Run the following command') }} (<a href="{{ config('app.freescout_repo') }}/wiki/Installation-Guide#6-configuring-web-server" target="_blank">{{ __('read more') }}</a>):<br/>
                                <code>sudo chown -R www-data:www-data {{ base_path() }}</code>
                            @elseif (!$perm['status'])
                                <strong class="text-danger">{{ __('Not writable') }} @if ($perm['value'])({{ $perm['value'] }})@endif</strong>
                            @else
                                <strong class="text-success">OK</strong>
                            @endif
                        @else
                            @if ($perm['status'])
                                <strong class="text-success">OK</strong>
                            @else
                                <strong class="text-danger">{{ __('Not writable') }} @if ($perm['value'])({{ $perm['value'] }})@endif</strong>

                                <br/><br/>
                                {{ __('Run the following command') }} (<a href="{{ config('app.freescout_repo') }}/wiki/Installation-Guide#6-configuring-web-server" target="_blank">{{ __('read more') }}</a>):<br/>
                                <code>sudo chown -R www-data:www-data {{ base_path() }}</code>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="table table-dark-header table-bordered table-responsive table-narrow">
        <tbody>
            <tr>
                <th>public/storage (symlink)</th>
                <td class="table-main-col">
                    @if ($public_symlink_exists)
                        <strong class="text-success">OK</strong>
                    @else
                        <strong class="text-danger">{{ __('Not found') }}</strong>
                        <div class="alert alert-danger margin-top-10">{{ __('Create symlink manually') }}: <code>ln -s storage/app/public public/storage</code></div>
                    @endif
                </td>
            </tr>
            <tr>
                <th>.env</th>
                <td class="table-main-col">
                    @if ($env_is_writable)
                        <strong class="text-success">OK</strong>
                    @else
                        <strong class="text-danger">{{ __('Not writable') }}</strong>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    @if ($invalid_symlinks)
        @include('modules/partials/invalid_symlinks')
    @endif

    @action('system.status.after_permissions')

    <h3 id="cron" class="margin-top-40">Cron Commands</h3>
    <p>
        {!! __('Make sure that you have the following line in your crontab:') !!}<br/>
        <code>* * * * * php {{ base_path() }}/artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
        <br/>
        {!! __('Alternatively cron job can be executed by requesting the following URL every minute (this method is not recommended as some features may not work as expected, use it at your own risk)') !!}:<br/>
        <a href="{{ route('system.cron', ['hash' => \Helper::getWebCronHash()]) }}" target="_blank">{{ route('system.cron', ['hash' => \Helper::getWebCronHash()]) }}</a>
    </p>
    <table class="table table-dark-header table-bordered table-responsive">
        <tbody>
            @foreach ($commands as $command)
                <tr>
                    <th>{{ $command['name'] }}</th>
                    <td class="table-main-col">
                        <strong class="text-@if ($command['status'] == "success"){{ 'success' }}@else{{ 'danger' }}@endif">{!! $command['status_text'] !!}</strong>
                        @if ($command['name'] == 'freescout:fetch-emails' && $command['status'] != "success")
                            (<a href="{{ route('logs', ['name' => 'fetch_errors']) }}">{{ __('See logs') }}</a>)
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @action('system.status.after_cron_commands')

    <h3 id="jobs" class="margin-top-40">{{ __('Background Jobs') }}</h3>
    @if (count($queued_jobs) || count($failed_jobs))
        {{ __('Queued and failed jobs are cleaned automatically once in a while. No need to worry or delete them manually.') }}
    @endif
    <table class="table table-dark-header table-bordered table-responsive">
        <tbody>
            <tr>
                <th>{{ __('Queued Jobs') }}</th>
                <td class="table-main-col">
                    <p>
                        {{ __('Total') }}: <strong>{{ count($queued_jobs)}}</strong>
                    </p>
                    <div class="jobs-list">
                        @foreach ($queued_jobs as $job)
                            @php
                                $payload = $job->getPayloadDecoded();
                            @endphp
                            @if ($payload)
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <th>{{ $loop->index+1 }}. {{ $payload['displayName'] }}</th>
                                            <th>
                                                <form action="{{ route('system.action') }}" method="POST" class="text-right">
                                                    {{ csrf_field() }}

                                                    <input type="hidden" name="job_id" value="{{ $job->id }}" />

                                                    <button type="submit" name="action" value="cancel_job" class="btn btn-default btn-xs margin-left-10">{{ __('Cancel') }}</button>
                                                    @if ($job->attempts > 0)
                                                        <button type="submit" name="action" value="retry_job" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-repeat"></i> {{ __('Retry') }}</button>
                                                    @endif
                                                </form>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Queue') }}</td>
                                            <td>{{ $job->queue }}</td>
                                        </tr>
                                        @if (\Str::startsWith($payload['displayName'], 'App\Jobs\Send'))
                                            @php
                                                $command = $job->getCommand();
                                                $last_thread = null;
                                                if ($command
                                                    && !empty($command->conversation)
                                                    && !empty($command->threads)
                                                ) {
                                                    $last_thread = \App\Thread::getLastThread($command->threads);
                                                }
                                            @endphp
                                            @if (!empty($last_thread))
                                                <tr>
                                                    <td>{{ __('Message') }}</td>
                                                    <td><a href="{{ route('conversations.view', ['id' => $last_thread->conversation_id]) }}#thread-{{ $last_thread->id }}" target="_blank">#{{ $command->conversation->number }}</a></td>
                                                </tr>
                                            @endif
                                        @endif
                                        <tr>
                                            <td>{{ __('Attempts') }}</td>
                                            <td>
                                                @if ($job->attempts > 0)<strong class="text-danger">@endif
                                                    {{ $job->attempts }}
                                                @if ($job->attempts > 0)
                                                    </strong>
                                                @endif
                                                @if ($job->attempts > 0 && !empty($last_thread))
                                                     &nbsp;<small>(<a href="{{ route('logs', ['name' => 'out_emails', 'thread_id' => $last_thread->id]) }}" target="_blank">{{ __('View log') }}</a>)</small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Created At') }}</td>
                                            <td>{{  App\User::dateFormat($job->created_at) }}</td>
                                        </tr>
                                        @if ($job->attempts > 0)
                                            <tr>
                                                <td>{{ __('Next Attempt') }}</td>
                                                <td>{{  App\User::dateFormat($job->available_at) }}</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            @endif
                        @endforeach
                    </div>
                </td>
            </tr>
            <tr>
                <th>{{ __('Failed Jobs') }}</th>
                <td>
                    <p>
                        {{ __('Total') }}:  <strong @if (count($failed_jobs) > 0) class="text-danger" @endif >{{ count($failed_jobs) }}</strong>

                        @if (count($failed_jobs))
                            &nbsp;&nbsp;
                            <form action="{{ route('system.action') }}" method="POST">
                                {{ csrf_field() }}

                                <select name="failed_queue" class="">
                                    @foreach ($failed_queues as $queue)
                                        <option value="{{ $queue }}">{{ __('Queue') }}: {{ $queue }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" name="action" value="delete_failed_jobs" class="btn btn-default btn-xs margin-left-10">{{ __('Delete') }}</button>
                                <button type="submit" name="action" value="retry_failed_jobs" class="btn btn-default btn-xs">{{ __('Retry') }}</button>
                            </form>
                        @endif
                    </p>
                    <div class="jobs-list">
                        @foreach ($failed_jobs as $job)
                            @php
                                $payload = $job->getPayloadDecoded();
                            @endphp
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th colspan="2">{{ $loop->index+1 }}. {{ json_decode($job->payload, true)['displayName'] }} – <small><a href="{{ route('system.ajax_html', ['action' => 'job_details', 'param' => $job->id]) }}" data-trigger="modal" data-modal-title="{{ $loop->index+1 }}. {{ json_decode($job->payload, true)['displayName'] }}" data-modal-no-footer="true">{{ __('View Details') }}</a></small></th>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Queue') }}</td>
                                        <td>{{ $job->queue }}</td>
                                    </tr>
                                    @if (\Str::startsWith($payload['displayName'], 'App\Jobs\Send'))
                                        @php
                                            $command = $job->getCommand();
                                            $last_thread = null;
                                            if ($command
                                                && !empty($command->conversation)
                                                && !empty($command->threads)
                                            ) {
                                                $last_thread = \App\Thread::getLastThread($command->threads);
                                            }
                                        @endphp
                                        @if (!empty($last_thread))
                                            <tr>
                                                <td>{{ __('Message') }}</td>
                                                <td><a href="{{ route('conversations.view', ['id' => $last_thread->conversation_id]) }}#thread-{{ $last_thread->id }}" target="_blank">#{{ $command->conversation->number }}</a></</td>
                                            </tr>
                                        <tr>
                                            <td>{{ __('Logs') }}</td>
                                            <td>
                                                <small><a href="{{ route('logs', ['name' => 'out_emails', 'thread_id' => $last_thread->id]) }}" target="_blank">{{ __('View log') }}</a></small>
                                            </td>
                                        </tr>
                                        @endif
                                    @endif
                                    <tr>
                                        <td>{{ __('Failed At') }}</td>
                                        <td>{{  App\User::dateFormat($job->failed_at, 'M j, Y H:i:s') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        @endforeach
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    @action('system.status.after_background_jobs')

</div>

@action('system.status.after_content')

@endsection

@section('javascript')
    @parent
    initSystemStatus();
@endsection