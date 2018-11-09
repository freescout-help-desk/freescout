<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Option;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

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
    public function view($section = 'general')
    {       
        $settings = $this->getSectionSettings($section);

        if (!$settings) {
            abort(404);
        }

        $sections = $this->getSections();

        $template_vars = [
            'settings' => $settings,
            'section' => $section,
            'sections' => $this->getSections(),
            'section_name' => $sections[$section]['title']
        ];
        $template_vars = $this->getTemplateVars($section, $template_vars);

        return view('settings/view', $template_vars);
    }

    public function getValidator($section)
    {
        switch ($section) {
            case 'emails':
                $rules = [
                    'settings.mail_from' => 'required|email',
                ];
                break;
            // default:
            //     $rules = \Event::fire('filter.settings_validate_rules');
            //     break;
        }

        if (!empty($rules)) {
            return Validator::make(request()->all(), $rules);
        }
        return null;
    }

    public function getTemplateVars($section, $template_vars)
    {
        switch ($section) {
            case 'emails':
                $template_vars['sendmail_path'] = ini_get('sendmail_path');
                $template_vars['mail_drivers'] = [
                    'mail'     => __("PHP's mail() function"),
                    'sendmail' => __("Sendmail"),
                    'smtp'     => 'SMTP',
                ];
                break;
            
            // default:
            //     $template_vars = \Event::fire('filter.settings_template_vars', [$template_vars]);
            //     break;
        }

        return $template_vars;
    }

    public function getSectionSettings($section)
    {
        switch ($section) {
            case 'general':
                $settings = [
                    'company_name'         => Option::get('company_name', \Config::get('app.name')),
                    'next_ticket'          => (Option::get('next_ticket') >= Conversation::max('number') + 1) ? Option::get('next_ticket') : Conversation::max('number') + 1,
                    'user_permissions'     => Option::get('user_permissions', []),
                    'email_branding'       => Option::get('email_branding'),
                    'open_tracking'        => Option::get('open_tracking'),
                    'enrich_customer_data' => Option::get('enrich_customer_data'),
                    'time_format'          => Option::get('time_format', User::TIME_FORMAT_24),
                ];
                break;
            case 'emails':
                $settings = [
                    'mail_from' => \App\Misc\Mail::getSystemMailFrom(),
                    'mail_driver' => Option::get('mail_driver', \Config::get('mail.driver')),
                    'mail_host' => Option::get('mail_host', \Config::get('mail.host')),
                    'mail_port' => Option::get('mail_port', \Config::get('mail.port')),
                    'mail_username' => Option::get('mail_username', \Config::get('mail.username')),
                    'mail_password' => Option::get('mail_password', \Config::get('mail.password')),
                    'mail_encryption' => Option::get('mail_encryption', \Config::get('mail.encryption')),
                ];
                break;
            case 'alerts':
                $settings = [
                    'alert_recipients'   => Option::get('alert_recipients'),
                    'alert_fetch'        => Option::get('alert_fetch'),
                    'alert_fetch_period' => Option::get('alert_fetch_period'),
                    'alert_send'         => Option::get('alert_send'),
                ];
                break;
            default:
                $settings = \Event::fire('filter.section_settings', [$section]);
                break;
        }

        return $settings;
    }

    public function getSections()
    {
        $sections = [
            // todo: order
            'general' => ['title' => __('General'), 'icon' => 'cog', 'order' => 100],
            'emails'  => ['title' => __('Mail Settings'), 'icon' => 'transfer', 'order' => 200],
            'alerts'  => ['title' => __('Alerts'), 'icon' => 'bell', 'order' => 300],
        ];
        //return \Event::fire('filter.settings_sections', [$sections]);
        return $sections;
    }

    /**
     * Save general settings.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function save($section = 'general')
    {
        $settings = $this->getSectionSettings($section);

        if (!$settings) {
            abort(404);
        }

        return $this->processSave($section, array_keys($settings));
    }

    public function processSave($section, $settings)
    {
        // Validate
        $validator = $this->getValidator($section);

        if ($validator && $validator->fails()) {
            return redirect()->route('settings', ['section' => $section])
                        ->withErrors($validator)
                        ->withInput();
        }

        $request = request();

        foreach ($settings as $i => $option_name) {
            // By some reason isset() does not work for empty elements
            if (array_key_exists($option_name, $request->settings)) {
                $option_value = $request->settings[$option_name];
                Option::set($option_name, $option_value);
            } else {
                Option::remove($option_name);
            }
        }

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('settings', ['section' => $section]);
    }

    /**
     * Users ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            // Test sending emails from mailbox
            case 'send_test':

                if (empty($request->to)) {
                    $response['msg'] = __('Please specify recipient of the test email');
                }

                if (!$response['msg']) {
                    $test_result = false;

                    try {
                        $test_result = \MailHelper::sendTestMail($request->to);
                    } catch (\Exception $e) {
                        $response['msg'] = $e->getMessage();
                    }

                    if (!$test_result && !$response['msg']) {
                        $response['msg'] = __('Error occurend sending email. Please check your mail server logs for more details.');
                    }
                }

                if (!$response['msg']) {
                    $response['status'] = 'success';
                }

                // Remember email address
                if (!empty($request->to)) {
                    \App\Option::set('send_test_to', $request->to);
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
