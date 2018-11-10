<?php

namespace App\Http\Controllers;

use App\Option;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\BufferedOutput;

class SystemController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * System status.
     */
    public function status(Request $request)
    {
        // PHP extensions
        $php_extensions = [];
        foreach (\Config::get('app.required_extensions') as $extension_name) {
            $alternatives = explode('/', $extension_name);
            if ($alternatives) {
                foreach ($alternatives as $alternative) {
                    $php_extensions[$extension_name] = extension_loaded(trim($alternative));
                    if ($php_extensions[$extension_name]) {
                        break;
                    }
                }
            } else {
                $php_extensions[$extension_name] = extension_loaded($extension_name);
            }
        }

        // Jobs
        $queued_jobs = \App\Job::orderBy('created_at', 'desc')->get();
        $failed_jobs = \App\FailedJob::orderBy('failed_at', 'desc')->get();

        // Commands
        $commands_list = ['freescout:fetch-emails', 'queue:work'];
        foreach ($commands_list as $command_name) {
            $status_texts = [];

            // Check if command is running now
            if (function_exists('shell_exec')) {
                $running_commands = 0;

                try {
                    $processes = preg_split("/[\r\n]/", shell_exec("ps aux | grep '{$command_name}'"));
                    $pids = [];
                    foreach ($processes as $process) {
                        preg_match("/^[\S]+\s+([\d]+)\s+/", $process, $m);
                        if (!preg_match("/(sh \-c|grep )/", $process) && !empty($m[1])) {
                            $running_commands++;
                            $pids[] = $m[1];
                        }
                    }
                } catch (\Exception $e) {
                    // Do nothing
                }
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
                        \Cache::forever('illuminate:queue:restart', Carbon::now()->getTimestamp());
                        $commands[] = [
                            'name'        => $command_name,
                            'status'      => 'error',
                            'status_text' => __(':number commands were running at the same time. Commands have been restarted', ['number' => $running_commands]),
                        ];
                    } else {
                        unset($pids[0]);
                        $commands[] = [
                            'name'        => $command_name,
                            'status'      => 'error',
                            'status_text' => __(':number commands are running at the same time. Please stop extra commands by executing the following console command:', ['number' => $running_commands]).' kill '.implode(' | kill ', $pids),
                        ];
                    }
                    continue;
                }
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
            if ($command_name == 'queue:work' && !$last_successful_run && function_exists('shell_exec')) {
                $status_texts[] = __('Cleared cache to force command to start.');
                \Artisan::call('cache:clear');
            }

            $commands[] = [
                'name'        => $command_name,
                'status'      => $status,
                'status_text' => implode(' ', $status_texts),
            ];
        }

        // Check new version
        $new_version_available = \Cache::remember('new_version_available', 15, function() {
            return \Updater::isNewVersionAvailable(\Config::get('app.version'));
        });

        return view('system/status', [
            'commands'       => $commands,
            'queued_jobs'    => $queued_jobs,
            'failed_jobs'    => $failed_jobs,
            'php_extensions' => $php_extensions,
            'new_version_available' => $new_version_available,
        ]);
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
            'output' => $output
        ]);
    }

    /**
     * Execute tools action.
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function toolsExecute(Request $request)
    {
        $outputLog = new BufferedOutput;

        switch ($request->action) {
            case 'clear_cache':
                \Artisan::call('freescout:clear-cache', [], $outputLog);
                break;

            case 'fetch_emails':
                \Artisan::call('freescout:fetch-emails', [], $outputLog);
                break;
        }

        $output = $outputLog->fetch();
        unset($outputLog);

        if ($output) {
            // \Session::flash does not work after BufferedOutput
            \Cache::forever('tools_execute_output', $output);
        }

        return redirect()->route('system.tools');
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
                    $response['msg'] = $e->getMessage();
                }
                if (!$response['msg'] && $status) {
                    // Adding session flash is useless as cache is cleated
                    $response['msg_success'] = __('Application successfully updated');
                    $response['status'] = 'success';
                }
                break;

            case 'check_updates':
                try {
                    $response['new_version_available'] = true; //\Updater::isNewVersionAvailable(config('app.version'));
                    \Cache::put('new_version_available', $response['new_version_available'], 15);
                    $response['status'] = 'success';
                } catch (\Exception $e) {
                    $response['msg'] = $e->getMessage();
                }
                if (!$response['msg'] && !$response['new_version_available']) {
                    // Adding session flash is useless as cache is cleated
                    $response['msg_success'] = __('You have the latest version of :app_name', ['app_name' => config('app.name')]);
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }
}
