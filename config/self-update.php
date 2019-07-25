<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default source repository type
    |--------------------------------------------------------------------------
    |
    | The default source repository type you want to pull your updates from.
    |
    */

    'default' => env('SELF_UPDATER_SOURCE', 'github'),

    /*
    |--------------------------------------------------------------------------
    | Version installed
    |--------------------------------------------------------------------------
    |
    | Set this to the version of your software installed on your system.
    |
    */

    'version_installed' => config('app.version'),

    /*
    |--------------------------------------------------------------------------
    | Repository types
    |--------------------------------------------------------------------------
    |
    | A repository can be of different types, which can be specified here.
    | Current options:
    | - github
    |
    */

    'repository_types' => [
        'github' => [
            'type'              => 'github',
            'repository_vendor' => 'freescout-helpdesk', //env('SELF_UPDATER_REPO_VENDOR', ''),
            'repository_name'   => 'freescout', //env('SELF_UPDATER_REPO_NAME', ''),
            'repository_url'    => '',
            'download_path'     => storage_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'updater', //env('SELF_UPDATER_DOWNLOAD_PATH', sys_get_temp_dir()),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude folders from update
    |--------------------------------------------------------------------------
    |
    | Specifiy folders which should not be updated and will be skipped during the
    | update process.
    |
    | Here's already a list of good examples to skip. You may want to keep those.
    |
    */

    'exclude_folders' => [
        '.git',
        'node_modules',
        'bootstrap/cache',
        'bower',
        'storage/app',
        'storage/framework',
        'storage/logs',
        'storage/self-update',
        'vendor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Logging
    |--------------------------------------------------------------------------
    |
    | Configure if fired events should be logged
    |
    */

    'log_events' => env('SELF_UPDATER_LOG_EVENTS', false),

    /*
    |--------------------------------------------------------------------------
    | Mail To Settings
    |--------------------------------------------------------------------------
    |
    | Configure if fired events should be logged
    |
    */

    'mail_to' => [
        'address'                  => env('SELF_UPDATER_MAILTO_ADDRESS', ''),
        'name'                     => env('SELF_UPDATER_MAILTO_NAME', ''),
        'subject_update_available' => env('SELF_UPDATER_MAILTO_UPDATE_AVAILABLE_SUBJECT', 'Update available'),
        'subject_update_succeeded' => env('SELF_UPDATER_MAILTO_UPDATE_SUCCEEDED_SUBJECT', 'Update succeeded'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Register custom artisan commands
    |---------------------------------------------------------------------------
    */

    'artisan_commands' => [
        'pre_update' => [
            //'command:signature' => [
            //    'class' => Command class
            //    'params' => []
            //]
        ],
        'post_update' => [
            'freescout:after-app-update' => [
                'class'  => \App\Console\Commands\AfterAppUpdate::class,
                'params' => [],
            ],
            // 'freescout:clear-cache' => [
            //     'class' => \App\Console\Commands\ClearCache::class,
            //     'params' => [],
            // ],
            // 'migrate' => [
            //     'class' => \Illuminate\Database\Console\Migrations\MigrateCommand::class,
            //     'params' => [
            //         '--force' => true,
            //     ]
            // ],
            // 'queue:restart' => [
            //     'class' => \Illuminate\Queue\Console\RestartCommand::class,
            //     'params' => [],
            // ],
        ],
    ],

];
