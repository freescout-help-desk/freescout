<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ActivityLog;

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
     * Logs
     *
     * @return \Illuminate\Http\Response
     */
    public function logs(Request $request)
    {
                //->where('preferences->dining->meal', 'salad')
                //->get();
        $names = ActivityLog::select('log_name')->distinct()->get()->pluck('log_name');
     
        $activities = [];
        $cols = [];
        $current_name = '';
        if (!empty($request->name)) {
            $activities = ActivityLog::inLog($request->name)->get();
            $current_name = $request->name;
        } elseif (count($names)) {
            $activities = ActivityLog::inLog($names[0])->get();
            $current_name = $names[0];
        }

        $logs = [];
        foreach ($activities as $activity) {
            $log = array(
                'date' => $activity->created_at,
                'user' => $activity->causer,
                'event' => $activity->getEventDescription(),
            );
            $cols = array('date', 'user', 'event');

            foreach ($activity->properties as $property_name => $property_value) {
                if (!is_string($property_value)) {
                    $property_value = json_encode($property_value);
                }
                $log[$property_name] = $property_value;
                $cols[] = $property_name;
            }

            $logs[] = $log;
        }

        return view('secure/logs', ['logs' => $logs, 'names' => $names, 'current_name' => $current_name, 'cols' => $cols]);
    }
}
