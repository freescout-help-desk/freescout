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

    <h3 id="app">{{ __('Info') }}</h3>

    <table class="table table-dark-header table-bordered table-responsive">
        <tbody>
            <tr id="version">
                <th>{{ __('App Version') }}</th>
                <td class="table-main-col">
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
                </td>
            </tr>
            <tr>
                <th>{{ __('Date & Time') }}</th>
                <td class="table-main-col">{{ App\User::dateFormat(new Illuminate\Support\Carbon()) }}</td>
            </tr>
            <tr>
                <th>{{ __('Timezone') }}</th>
                <td class="table-main-col">{{ \Config::get('app.timezone') }} (GMT{{ date('O') }})</td>
            </tr>
            <tr>
                <th>{{ __('Protocol') }}</th>
                <td class="table-main-col" id="system-app-protocol"></td>
            </tr>
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
                <th>{{ __('Web Server') }}</th>
                <td class="table-main-col">@if (!empty($_SERVER['SERVER_SOFTWARE'])){{ $_SERVER['SERVER_SOFTWARE'] }}@else ? @endif</td>
            </tr>
            <tr>
                <th>{{ __('PHP Version') }}</th>
                <td class="table-main-col">PHP {{ phpversion() }}</td>
            </tr>
        </tbody>
    </table>

    <h3 id="php">{{ __('PHP Extensions') }}</h3>
    <table class="table table-dark-header table-bordered table-responsive table-narrow">
        <tbody>
            @foreach ($php_extensions as $extension_name => $extension_status)
                <tr>
                    <th>{{ $extension_name }}</th>
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

    <h3 id="permissions">{{ __('Permissions') }}</h3>
    {!! __('These folders must be writable by web server user (:user).', ['user' => '<strong>'.get_current_user().'</strong>']) !!} {{ __('Recommended permissions') }}: <strong>775</strong>
    <table class="table table-dark-header table-bordered table-responsive table-narrow">
        <tbody>
            @foreach ($permissions as $perm_path => $perm)
                <tr>
                    <th>{{ $perm_path }}</th>
                    <td class="table-main-col">
                        @if ($perm['status'])
                            <strong class="text-success">OK</strong>
                        @else
                            <strong class="text-danger">{{ __('Not writable') }} @if ($perm['value'])({{ $perm['value'] }})@endif</strong>
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

    <h3 id="cron" class="margin-top-40">Cron Commands</h3>
    <p>
        {!! __('Make sure that you have the following line in your crontab:') !!}<br/>
        <code>* * * * * php {{ base_path() }}/artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
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
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th>{{ $loop->index+1 }}. {{ json_decode($job->payload, true)['displayName'] }}</th>
                                        <th>
                                            <form action="{{ route('system.action') }}" method="POST" class="text-right">
                                                {{ csrf_field() }}

                                                <input type="hidden" name="job_id" value="{{ $job->id }}" />

                                                <button type="submit" name="action" value="cancel_job" class="btn btn-default btn-xs margin-left-10">{{ __('Cancel') }}</button>
                                            </form>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Queue') }}</td>
                                        <td>{{ $job->queue }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Attempts') }}</td>
                                        <td>
                                            @if ($job->attempts > 0)<strong class="text-danger">@endif
                                                {{ $job->attempts }}
                                            @if ($job->attempts > 0)</strong>@endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Created At') }}</td>
                                        <td>{{  App\User::dateFormat($job->created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
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
                                        <option value="{{ $queue }}">{{ $queue }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" name="action" value="delete_failed_jobs" class="btn btn-default btn-xs margin-left-10">{{ __('Delete') }}</button>
                                <button type="submit" name="action" value="retry_failed_jobs" class="btn btn-default btn-xs">{{ __('Retry') }}</button>
                            </form>
                        @endif
                    </p>
                    <div class="jobs-list">
                        @foreach ($failed_jobs as $job)
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th colspan="2">{{ $loop->index+1 }}. {{ json_decode($job->payload, true)['displayName'] }}</th>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Queue') }}</td>
                                        <td>{{ $job->queue }}</td>
                                    </tr>
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

</div>
@endsection

@section('javascript')
    @parent
    initSystemStatus();
@endsection