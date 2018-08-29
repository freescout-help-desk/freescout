<?php

namespace App\Http\Controllers;

use App\Option;
use App\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
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
     * General settings
     *
     * @return \Illuminate\Http\Response
     */
    public function general()
    {
        $settings = [];

        $settings['company_name'] = Option::get('company_name', \Config::get('app.name'));
        $settings['next_ticket'] = Option::get('next_ticket');
        $settings['user_permissions'] = Option::get('user_permissions', []);
        $settings['open_tracking'] = Option::get('open_tracking');
        $settings['enrich_customer_data'] = Option::get('enrich_customer_data');
        $settings['time_format'] = Option::get('time_format', User::TIME_FORMAT_24);

        return view('settings/general', ['settings' => $settings]);
    }

    /**
     * Save general settings.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function generalSave(Request $request)
    {
        $settings = [
            'company_name',
            'next_ticket',
            'user_permissions',
            'open_tracking',
            'enrich_customer_data',
            'time_format',
        ];

        // Validation is not needed

        foreach ($settings as $i => $option_name) {
            if (isset($request->settings[$option_name])) {
                $option_value = $request->settings[$option_name];
                Option::set($option_name, $option_value);
            } else {
                Option::remove($option_name);
            }
        }

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('settings');
    }
}
