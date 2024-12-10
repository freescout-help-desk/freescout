<?php

namespace App\Http\Controllers;

use App\Option;
use App\User;
use App\FailedJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\BufferedOutput;

class SystemController extends Controller
{
    public static $latest_version_error = '';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => [
            'cron'
        ]]);
    }

    /**
     * System status.
     */
    public function status(Request $request)
    {
        // PHP extensions.
        $php_extensions = \Helper::checkRequiredExtensions();

        // Functions.
        $functions = \Helper::checkRequiredFunctions();

        // Permissions.
        $permissions = [];
        foreach (config('installer.permissions') as $perm_path => $perm_value) {
            $path = base_path($perm_path);
            $value = '';
            if (file_exists($path)) {
                $value = substr(sprintf('%o', fileperms($path)), -4);
            }
            $permissions[$perm_path] = [
                'status' => \Helper::isFolderWritable($path),
                'value'  => $value,
            ];
        }

        // Check if cache files are writable.
        $non_writable_cache_file = '';
        if (function_exists('shell_exec')) {
            $non_writable_cache_file = \Helper::shellExec('find '.base_path('storage/framework/cache/data/').' -type f | xargs -I {} sh -c \'[ ! -w "{}" ] && echo {}\' 2>&1 | head -n 1');
            $non_writable_cache_file = trim($non_writable_cache_file ?? '');
            // Leave only one line (in case head -n 1 does not work)
            $non_writable_cache_file = preg_replace("#[\r\n].+#m", '', $non_writable_cache_file);
            if (!strstr($non_writable_cache_file, base_path('storage/framework/cache/data/'))) {
                $non_writable_cache_file = '';
            }
        }
        

        // Check if public symlink exists, if not, try to create.
        $public_symlink_exists = true;
        $public_path = public_path('storage');
        $public_test = $public_path.DIRECTORY_SEPARATOR.'.gitignore';

        if (!file_exists($public_test) || !file_get_contents($public_test)) {
            \File::delete($public_path);
            \Artisan::call('storage:link');
            if (!file_exists($public_test) || !file_get_contents($public_test)) {
                $public_symlink_exists = false;
            }
        }

        // Check if .env is writable.
        $env_is_writable = is_writable(base_path('.env'));

        // Jobs
        $queued_jobs = \App\Job::orderBy('created_at', 'desc')->limit(100)->get();
        $failed_jobs = \App\FailedJob::orderBy('failed_at', 'desc')->limit(100)->get();
        $failed_queues = $failed_jobs->pluck('queue')->unique();

        // Commands
        $commands_list = [
            'freescout:fetch-emails' => 'freescout:fetch-emails',
            \Helper::getWorkerIdentifier() => 'queue:work'
        ];
        foreach ($commands_list as $command_identifier => $command_name) {
            $status_texts = [];

            // Check if command is running now
            $running_commands = 0;
            $pids = \Helper::getRunningProcesses($command_identifier);
            $running_commands = count($pids);

            if ($running_commands == 1) {
                $commands[] = [
                    'name'        => $command_name,
                    'status'      => 'success',
                    'status_text' => __('Running'),
                ];
                continue;
            } elseif ($running_commands > 1) {
                // queue:work command is stopped by settings a cache key
                if ($command_name == 'queue:work') {
                    \Helper::queueWorkerRestart();
                    $commands[] = [
                        'name'        => $command_name,
                        'status'      => 'error',
                        'status_text' => __(':number commands were running at the same time. Commands have been restarted', ['number' => $running_commands]),
                    ];
                } else {
                    array_shift($pids); // Remove first PID
                    $commands[] = [
                        'name'        => $command_name,
                        'status'      => 'error',
                        'status_text' => __(':number commands are running at the same time. Please stop extra commands by executing the following console command:', ['number' => $running_commands]).' kill '.implode(' | kill ', $pids),
                    ];
                }
                continue;
            }

            // Check last run
            $option_name = str_replace('freescout_', '', preg_replace('/[^a-zA-Z0-9]/', '_', $command_name));

            $date_text = '?';
            $last_run = Option::get($option_name.'_last_run');
            if ($last_run) {
                $date = Carbon::createFromTimestamp($last_run);
                $date_text = User::dateFormat($date);
            }
            $status_texts[] = __('Last run:').' '.$date_text;

            $date_text = '?';
            $last_successful_run = Option::get($option_name.'_last_successful_run');
            if ($last_successful_run) {
                $date_ = Carbon::createFromTimestamp($last_successful_run);
                $date_text = User::dateFormat($date);
            }
            $status_texts[] = __('Last successful run:').' '.$date_text;

            $status = 'error';
            if ($last_successful_run && $last_run && (int) $last_successful_run >= (int) $last_run) {
                unset($status_texts[0]);
                $status = 'success';
            }

            // If queue:work is not running, clear cache to let it start if something is wrong with the mutex
            if ($command_name == 'queue:work' && !$last_successful_run) {
                $status_texts[] = __('Try to :%a_start%clear cache:%a_end% to force command to start.', ['%a_start%' => '<a href="'.route('system.tools').'" target="_blank">', '%a_end%' => '</a>']);
            }

            $commands[] = [
                'name'        => $command_name,
                'status'      => $status,
                'status_text' => implode(' ', $status_texts),
            ];
        }

        // Check new version if enabled
        $new_version_available = false;
        if (!\Config::get('app.disable_updating')) {
            $latest_version = \Cache::remember('latest_version', 15, function () {
                try {
                    return \Updater::getVersionAvailable();
                } catch (\Exception $e) {
                    SystemController::$latest_version_error = $e->getMessage();
                    return '';
                }
            });

            if ($latest_version && version_compare($latest_version, \Config::get('app.version'), '>')) {
                $new_version_available = true;
            }
        } else {
            $latest_version = \Config::get('app.version');
        }

        // Detect missing migrations.
        $migrations_output = \Helper::runCommand('migrate:status');
        preg_match_all("#\| N    \| ([^\|]+)\|#", $migrations_output, $migrations_m);
        $missing_migrations = $migrations_m[1] ?? [];

        return view('system/status', [
            'commands'              => $commands,
            'queued_jobs'           => $queued_jobs,
            'failed_jobs'           => $failed_jobs,
            'failed_queues'         => $failed_queues,
            'php_extensions'        => $php_extensions,
            'functions'             => $functions,
            'permissions'           => $permissions,
            'new_version_available' => $new_version_available,
            'latest_version'        => $latest_version,
            'latest_version_error'  => SystemController::$latest_version_error,
            'public_symlink_exists' => $public_symlink_exists,
            'env_is_writable'       => $env_is_writable,
            'non_writable_cache_file' => $non_writable_cache_file,
            'missing_migrations'    => $missing_migrations,
            'invalid_symlinks'      => \App\Module::checkSymlinks(),
        ]);
    }

    public function action(Request $request)
    {
        switch ($request->action) {
            case 'cancel_job':
                \App\Job::where('id', $request->job_id)->delete();
                \Session::flash('flash_success_floating', __('Done'));
                break;

            case 'retry_job':
                \App\Job::where('id', $request->job_id)->update(['available_at' => time()]);
                sleep(1);
                \Session::flash('flash_success_floating', __('Done'));
                break;

            case 'delete_failed_jobs':
                \App\FailedJob::where('queue', $request->failed_queue)->delete();
                \Session::flash('flash_success_floating', __('Failed jobs deleted'));
                break;

            case 'retry_failed_jobs':
                $jobs = \App\FailedJob::where('queue', $request->failed_queue)->get();
                foreach ($jobs as $job) {
                    \Artisan::call('queue:retry', ['id' => $job->id]);
                }
                \Session::flash('flash_success_floating', __('Failed jobs restarted'));
                break;
        }

        return redirect()->route('system');
    }

    /**
     * System tools.
     */
    public function tools(Request $request)
    {
        $output = \Cache::get('tools_execute_output');
        if ($output) {
            \Cache::forget('tools_execute_output');
        }

        return view('system/tools', [
            'output' => $output,
        ]);
    }

    /**
     * Execute tools action.
     *
     * @param Request $request [description]
     *
     * @return [type] [description]
     */
    public function toolsExecute(Request $request)
    {
        $outputLog = new BufferedOutput();

        switch ($request->action) {
            case 'clear_cache':
                \Artisan::call('freescout:clear-cache', [], $outputLog);
                break;

            case 'fetch_emails':
                $params = [];
                $params['--days'] = (int)$request->days;
                $params['--unseen'] = (int)$request->unseen;
                $params['--debug'] = (int)$request->debug;
                \Artisan::call('freescout:fetch-emails', $params, $outputLog);
                break;

            case 'migrate_db':
                \Artisan::call('migrate', ['--force' => true], $outputLog);
                break;

            case 'logout_users':
                \Artisan::call('freescout:logout-users', [], $outputLog);
                break;
        }

        $output = $outputLog->fetch();
        unset($outputLog);

        if ($output) {
            // \Session::flash does not work after BufferedOutput
            \Cache::forever('tools_execute_output', $output);
        }

        return redirect()->route('system.tools')->withInput($request->input());
    }

    /**
     * Ajax.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            case 'update':
                try {
                    $status = \Updater::update();

                    // Artisan::output()
                } catch (\Exception $e) {
                    $response['msg'] = __('Error occurred. Please try again or try another :%a_start%update method:%a_end%', ['%a_start%' => '<a href="'.config('app.freescout_url').'/docs/update/" target="_blank">', '%a_end%' => '</a>']);
                    $response['msg'] .= '<br/><br/>'.$e->getMessage();

                    \Helper::logException($e);
                }
                if (!$response['msg'] && $status) {
                    // Adding session flash is useless as cache is cleared
                    $response['msg_success'] = __('Application successfully updated');
                    $response['status'] = 'success';
                }
                break;

            case 'check_updates':
                if (!\Config::get('app.disable_updating')) {
                    try {
                        $response['new_version_available'] = \Updater::isNewVersionAvailable(config('app.version'));
                        $response['status'] = 'success';
                    } catch (\Exception $e) {
                        $response['msg'] = __('Error occurred').': '.$e->getMessage();
                    }
                    if (!$response['msg'] && !$response['new_version_available']) {
                        // Adding session flash is useless as cache is cleated
                        $response['msg_success'] = __('You have the latest version installed');
                    }
                } else {
                    $response['msg_success'] = __('You have the latest version installed');
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occurred';
        }

        return \Response::json($response);
    }

    /**
     * Web Cron.
     */
    public function cron(Request $request)
    {
        if (empty($request->hash) || $request->hash != \Helper::getWebCronHash()) {
            abort(404);
        }
        $outputLog = new BufferedOutput();
        \Artisan::call('schedule:run', [], $outputLog);
        $output = $outputLog->fetch();

        return response($output, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Ajax HTML.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'job_details':
                $job = \App\FailedJob::find($request->param);
                if (!$job) {
                    abort(404);
                }

                $html = '';
                $payload = json_decode($job->payload, true);

                if (!empty($payload['data']['command'])) {
                    $html .= '<pre>'.print_r(unserialize($payload['data']['command']), 1).'</pre>';
                }

                $html .= '<pre>'.$job->exception.'</pre>';

                return response($html);
        }

        abort(404);
    }
}
