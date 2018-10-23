<?php

namespace RachidLaasri\LaravelInstaller\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use RachidLaasri\LaravelInstaller\Helpers\EnvironmentManager;
use RachidLaasri\LaravelInstaller\Events\EnvironmentSaved;
use Validator;
use Illuminate\Validation\Rule;

class EnvironmentController extends Controller
{
    /**
     * @var EnvironmentManager
     */
    protected $EnvironmentManager;

    /**
     * @param EnvironmentManager $environmentManager
     */
    public function __construct(EnvironmentManager $environmentManager)
    {
        $this->EnvironmentManager = $environmentManager;
    }

    /**
     * Display the Environment menu page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentMenu()
    {
        return view('vendor.installer.environment');
    }

    /**
     * Display the Environment page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentWizard()
    {
        $envConfig = $this->EnvironmentManager->getEnvContent();

        return view('vendor.installer.environment-wizard', compact('envConfig'));
    }

    /**
     * Display the Environment page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentClassic()
    {
        $envConfig = $this->EnvironmentManager->getEnvContent();

        return view('vendor.installer.environment-classic', compact('envConfig'));
    }

    /**
     * Processes the newly saved environment configuration (Classic).
     *
     * @param Request $input
     * @param Redirector $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveClassic(Request $input, Redirector $redirect)
    {
        $message = $this->EnvironmentManager->saveFileClassic($input);

        event(new EnvironmentSaved($input));

        return $redirect->route('LaravelInstaller::environmentClassic')
                        ->with(['message' => $message]);
    }

    // Save old values to sessions
    public function rememberOldRequest($request)
    {
        foreach ($request->all() as $field => $value) {
            session(['_old_input.'.$field => $value]);
        }
    }

    /**
     * Processes the newly saved environment configuration (Form Wizard).
     *
     * @param Request $request
     * @param Redirector $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveWizard(Request $request, Redirector $redirect)
    {
        $rules = config('installer.environment.form.rules');
        $messages = [
            'environment_custom.required_if' => trans('installer_messages.environment.wizard.form.name_required'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($request->app_force_https == 'true') {
            $request->merge(['app_url' => preg_replace("/^http:/i", 'https:', $request->app_url)]);
        }

        $this->rememberOldRequest($request);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return view('vendor.installer.environment-wizard', compact('errors', 'envConfig'));
        }

        // Check DB connection
        //$this->EnvironmentManager->saveFileWizard($request);
        try {
            \Config::set("database.connections.install", [
                'driver'    => 'mysql',
                "host"      => $request->database_hostname,
                "database"  => $request->database_name,
                "username"  => $request->database_username,
                "password"  => $request->database_password,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ]);
            \DB::connection('install')->getPdo();
        } catch (\Exception $e) {

            $validator->getMessageBag()->add('general', 'Could not establish database connection: '.$e->getMessage());
            $validator->getMessageBag()->add('database_hostname', 'Database Host: Please check entered value.');
            $validator->getMessageBag()->add('database_port', 'Database Port: Please check entered value.');
            $validator->getMessageBag()->add('database_name', 'Database Name: Please check entered value.');
            $validator->getMessageBag()->add('database_username', 'Database User Name: Please check entered value.');
            $validator->getMessageBag()->add('database_password', 'Database Password: Please check entered value.');
            $errors = $validator->errors();

            // We have to write request to session again, as saveFileWizard() clears the cache and session
            $this->rememberOldRequest($request);

            return view('vendor.installer.environment-wizard', compact('errors', 'envConfig'));
        }

        $results = $this->EnvironmentManager->saveFileWizard($request);

        event(new EnvironmentSaved($request));

        return $redirect->route('LaravelInstaller::database')
                        ->with(['results' => $results]);
    }
}
