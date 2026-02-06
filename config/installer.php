<?php

// Causes:
// Call to undefined method Illuminate\Validation\Rules\In::__set_state()
//use Illuminate\Validation\Rule;

return [

    /*
    |--------------------------------------------------------------------------
    | Server Requirements
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel server requirements, you can add as many
    | as your application require, we check if the extension is enabled
    | by looping through the array and run "extension_loaded" on it.
    |
    */
    'core' => [
        'minPhpVersion' => '7.1.0',
        'maxPhpVersion' => '8.99.99',
    ],
    'final' => [
        'key'     => false,
        'publish' => false,
    ],
    // It must be equal to app.required_extensions
    'requirements' => [
        'php' => [
            'OpenSSL',
            'PDO',
            'Mbstring',
            'Tokenizer',
            'JSON',
            'XML',
            'IMAP',
            'GD',
            'fileinfo',
            'ZIP',
            'iconv',
            'cURL',
            'DOM',
            'libxml',
            //'pcntl',
            // We keep it as optional, as it's only used to translate dates.
            //'intl',
        ],
        // 'apache' => [
        //     'mod_rewrite',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Folders Permissions
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel folders permissions, if your application
    | requires more permissions just add them to the array list bellow.
    |
    */
    'permissions' => [
        'storage/app/'                      => '775',
        'storage/framework/'                => '775',
        'storage/framework/cache/data/'     => '775',
        'storage/logs/'                     => '775',
        'bootstrap/cache/'                  => '775',
        'public/css/builds/'                => '775',
        'public/js/builds/'                 => '775',
        'public/modules/'                   => '775',
        'Modules/'                          => '775',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Form Wizard Validation Rules & Messages
    |--------------------------------------------------------------------------
    |
    | This are the default form vield validation rules. Available Rules:
    | https://laravel.com/docs/5.4/validation#available-validation-rules
    |
    */
    'environment' => [
        'form' => [
            'rules' => [
                // 'app_name'              => 'required|string|max:50',
                // 'environment'           => 'required|string|max:50',
                // 'environment_custom'    => 'required_if:environment,other|max:50',
                // // 'app_debug'             => [
                // //     'required',
                // //     Rule::in(['true', 'false']),
                // // ],
                // 'app_log_level'         => 'required|string|max:50',
                'app_url'               => 'required|url',
                'database_connection'   => 'required|string|max:1000',
                'database_hostname'     => 'required|string|max:1000',
                'database_port'         => 'required|numeric',
                'database_name'         => 'required|string|max:1000',
                'database_username'     => 'required|string|max:1000',
                'database_password'     => 'required|string|max:1000',
                'admin_email'           => 'required|email',
                'admin_first_name'      => 'required|string|max:20',
                'admin_last_name'       => 'required|string|max:30',
                'admin_password'        => 'required|string',
                // 'broadcast_driver'      => 'required|string|max:50',
                // 'cache_driver'          => 'required|string|max:50',
                // 'session_driver'        => 'required|string|max:50',
                // 'queue_driver'          => 'required|string|max:50',
                // 'redis_hostname'        => 'required|string|max:50',
                // 'redis_password'        => 'required|string|max:50',
                // 'redis_port'            => 'required|numeric',
                // 'mail_driver'           => 'required|string|max:50',
                // 'mail_host'             => 'required|string|max:50',
                // 'mail_port'             => 'required|string|max:50',
                // 'mail_username'         => 'required|string|max:50',
                // 'mail_password'         => 'required|string|max:50',
                // 'mail_encryption'       => 'required|string|max:50',
                // 'pusher_app_id'         => 'max:50',
                // 'pusher_app_key'        => 'max:50',
                // 'pusher_app_secret'     => 'max:50',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Installed Middlware Options
    |--------------------------------------------------------------------------
    | Different available status switch configuration for the
    | canInstall middleware located in `canInstall.php`.
    |
    */
    'installed' => [
        'redirectOptions' => [
            'route' => [
                'name' => 'dashboard',
                'data' => [],
            ],
            'abort' => [
                'type' => '404',
            ],
            'dump' => [
                'data' => 'Dumping a not found message.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Selected Installed Middlware Option
    |--------------------------------------------------------------------------
    | The selected option fo what happens when an installer intance has been
    | Default output is to `/resources/views/error/404.blade.php` if none.
    | The available middleware options include:
    | route, abort, dump, 404, default, ''
    |
    */
    'installedAlreadyAction' => 'route',

    /*
    |--------------------------------------------------------------------------
    | Updater Enabled
    |--------------------------------------------------------------------------
    | Can the application run the '/update' route with the migrations.
    | The default option is set to False if none is present.
    | Boolean value
    |
    */
    'updaterEnabled' => 'false',

];
