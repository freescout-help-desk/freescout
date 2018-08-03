<?php

namespace App\Http\Controllers;

use App\ActivityLog;
use Illuminate\Http\Request;

class SecureController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        $mailboxes = auth()->user()->mailboxesCanView();

        return view('secure/dashboard', ['mailboxes' => $mailboxes]);
    }

    /**
     * Logs.
     *
     * @return \Illuminate\Http\Response
     */
    public function logs(Request $request)
    {
        function addCol($cols, $col)
        {
            if (!in_array($col, $cols)) {
                $cols[] = $col;
            }
            return $cols;
        }
        $names = ActivityLog::select('log_name')->distinct()->get()->pluck('log_name');

        $activities = [];
        $cols = [];
        $current_name = '';
        if (!empty($request->name)) {
            $activities = ActivityLog::inLog($request->name)->orderBy('created_at', 'desc')->get();
            $current_name = $request->name;
        } elseif (count($names)) {
            $activities = ActivityLog::inLog($names[0])->orderBy('created_at', 'desc')->get();
            $current_name = $names[0];
        }

        $logs = [];
        $cols = ['date'];
        foreach ($activities as $activity) {
            $log = [];
            $log['date'] = $activity->created_at;
            if ($activity->causer) {
                $log['causer'] = $activity->causer;
                if ($activity->causer_type == 'App\User') {
                    $cols = addCol($cols, 'user');
                } else {
                    $cols = addCol($cols, 'customer');
                }
            }
            $log['event'] = $activity->getEventDescription();

            $cols = addCol($cols, 'event');

            foreach ($activity->properties as $property_name => $property_value) {
                if (!is_string($property_value)) {
                    $property_value = json_encode($property_value);
                }
                $log[$property_name] = $property_value;
                $cols = addCol($cols, $property_name);
            }

            $logs[] = $log;
        }

        return view('secure/logs', ['logs' => $logs, 'names' => $names, 'current_name' => $current_name, 'cols' => $cols]);
    }

    /**
     * System status.
     */
    public function system(Request $request)
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

        return view('secure/system', [
            'queued_jobs'    => $queued_jobs,
            'failed_jobs'    => $failed_jobs,
            'php_extensions' => $php_extensions
        ]);
    }
}
