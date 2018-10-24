<?php

namespace App\Http\Controllers;

use App\ActivityLog;
use App\Option;
use App\SendLog;
use App\Thread;
use App\User;
use Carbon\Carbon;
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

        // No need to check permissions here, as they are checked in routing

        $names = ActivityLog::select('log_name')->distinct()->pluck('log_name')->toArray();

        $activities = [];
        $cols = [];
        $page_size = 20;
        $name = '';

        if (!empty($request->name)) {
            $activities = ActivityLog::inLog($request->name)->orderBy('created_at', 'desc')->paginate($page_size);
            $name = $request->name;
        } elseif (count($names)) {
            $name =  ActivityLog::NAME_OUT_EMAILS;
            // $activities = ActivityLog::inLog($names[0])->orderBy('created_at', 'desc')->paginate($page_size);
            // $name = $names[0];
        }

        if ($name != ActivityLog::NAME_OUT_EMAILS) {
            $logs = [];
            $cols = ['date'];
            foreach ($activities as $activity) {
                $log = [];
                $log['date'] = $activity->created_at;
                if ($activity->causer) {
                    if ($activity->causer_type == 'App\User') {
                        $cols = addCol($cols, 'user');
                        $log['user'] = $activity->causer;
                    } else {
                        $cols = addCol($cols, 'customer');
                        $log['customer'] = $activity->causer;
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
        } else {
            // Outgoing emails are displayed from send log
            $logs = [];
            $cols = [
                'date',
                'type',
                'email',
                'status',
                'conversation',
                'user',
                'customer',
            ];

            $activities = SendLog::orderBy('created_at', 'desc')->paginate($page_size);

            foreach ($activities as $record) {

                $conversation = '';
                if ($record->thread_id) {
                    $conversation = Thread::find($record->thread_id);
                }
                
                $status = $record->getStatusName();
                if ($record->status_message) {
                    $status .= '. '.$record->status_message;
                }

                $logs[] = [
                    'date'          => $record->created_at,
                    'type'          => $record->getMailTypeName(),
                    'email'         => $record->email,
                    'status'        => $status,
                    'conversation'  => $conversation,
                    'user'          => $record->user,
                    'customer'      => $record->customer,
                ];
            }

        }

        array_unshift($names, ActivityLog::NAME_OUT_EMAILS);

        if (!in_array($name, $names)) {
            $names[] = $name;
        }

        return view('secure/logs', [
            'logs' => $logs, 
            'names' => $names,
            'current_name' => $name,
            'cols' => $cols,
            'activities' => $activities
        ]);
    }

    /**
     * Logs page submitted
     */
    public function logsSubmit(Request $request)
    {
        // No need to check permissions here, as they are checked in routing

        $name = '';
        if (!empty($request->name)) {
            $activities = ActivityLog::inLog($request->name)->orderBy('created_at', 'desc')->get();
            $name = $request->name;
        } elseif (count($names = ActivityLog::select('log_name')->distinct()->get()->pluck('log_name'))) {
            $name =  ActivityLog::NAME_OUT_EMAILS;
            // $activities = ActivityLog::inLog($names[0])->orderBy('created_at', 'desc')->get();
            // $name = $names[0];
        }

        switch ($request->action) {
            case 'clean':
                if ($name && $name != ActivityLog::NAME_OUT_EMAILS) {
                    ActivityLog::where('log_name', $name)->delete();
                    \Session::flash('flash_success_floating', __('Log successfully cleared'));
                }
                break;
        }

        return redirect()->route('logs', ['name' => $name]);
    }
}
