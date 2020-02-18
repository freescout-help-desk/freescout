<?php

return [

    /*
     *
     * Shared translations.
     *
     */
    'title'  => 'FreeScout Installatie',
    'next'   => 'Volgende Stap',
    'back'   => 'Vorige',
    'finish' => 'Installeren',
    'forms'  => [
        'errorTitle' => 'De volgende fouten zijn opgetreden:',
    ],

    /*
     *
     * Home page translations.
     *
     */
    'welcome' => [
        'templateTitle' => 'Welkom',
        'title'         => 'FreeScout Installatie',
        'message'       => 'Eenvoudige Installatie en Setup Wizard.',
        'next'          => 'Controleer Vereisten',
    ],

    /*
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'templateTitle' => 'Stap 1 | Server Vereisten',
        'title'         => 'Server Vereisten',
        'next'          => 'Controleer Permissies',
    ],

    /*
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'templateTitle' => 'Stap 2 | Permissies',
        'title'         => 'Permissies',
        'next'          => 'Omgeving Instellen',
    ],

    /*
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle'  => 'Stap 3 | Instellingen van Omgeving',
            'title'          => 'Instellingen van Omgeving',
            'desc'           => 'Selecteer aub hoe u het <code>.env</code> bestand van de app wilt configureren.',
            'wizard-button'  => 'Form Wizard Setup',
            'classic-button' => 'Klassieke Tekst Editor',
        ],
        'wizard' => [
            'templateTitle' => 'Stap 3 | Instellingen van Omgeving | Stap voor stap Wizard',
            'title'         => 'Stap voor stap <code>.env</code> Wizard',
            'tabs'          => [
                'environment' => 'Omgeving',
                'database'    => 'Database',
                'application' => 'Applicatie',
            ],
            'form' => [
                'name_required'                      => 'Omgevingsnaam is verplicht.',
                'app_name_label'                     => 'App Naam',
                'app_name_placeholder'               => 'App Naam',
                'app_environment_label'              => 'Appomgeving',
                'app_environment_label_local'        => 'Lokaal',
                'app_environment_label_developement' => 'Development',
                'app_environment_label_qa'           => 'Qa',
                'app_environment_label_production'   => 'Productie',
                'app_environment_label_other'        => 'Ander',
                'app_environment_placeholder_other'  => 'Voer omgeving in...',
                'app_debug_label'                    => 'Debug App',
                'app_debug_label_true'               => 'Waar',
                'app_debug_label_false'              => 'Onwaar',
                'app_log_level_label'                => 'App Log Niveau',
                'app_log_level_label_debug'          => 'debug',
                'app_log_level_label_info'           => 'info',
                'app_log_level_label_notice'         => 'bericht',
                'app_log_level_label_warning'        => 'waarschuwing',
                'app_log_level_label_error'          => 'fout',
                'app_log_level_label_critical'       => 'kritieke',
                'app_log_level_label_alert'          => 'alert',
                'app_log_level_label_emergency'      => 'noodgeval',
                'app_url_label'                      => 'App Url',
                'app_url_placeholder'                => 'App Url',
                'db_connection_label'                => 'Database Verbinding',
                'db_connection_label_mysql'          => 'mysql',
                'db_connection_label_sqlite'         => 'sqlite',
                'db_connection_label_pgsql'          => 'pgsql',
                'db_connection_label_sqlsrv'         => 'sqlsrv',
                'db_host_label'                      => 'Database Host',
                'db_host_placeholder'                => 'Database Host',
                'db_port_label'                      => 'Database Poort',
                'db_port_placeholder'                => 'Database Poort',
                'db_name_label'                      => 'Database Naam',
                'db_name_placeholder'                => 'Database Naam',
                'db_username_label'                  => 'Database User Naam',
                'db_username_placeholder'            => 'Database User Naam',
                'db_password_label'                  => 'Database Wachtwoord',
                'db_password_placeholder'            => 'Database Wachtwoord',

                'app_tabs' => [
                    'more_info'                => 'Meer Info',
                    'broadcasting_title'       => 'Broadcasting, Caching, Session, &amp; Queue',
                    'broadcasting_label'       => 'Broadcast Driver',
                    'broadcasting_placeholder' => 'Broadcast Driver',
                    'cache_label'              => 'Cache Driver',
                    'cache_placeholder'        => 'Cache Driver',
                    'session_label'            => 'Session Driver',
                    'session_placeholder'      => 'Session Driver',
                    'queue_label'              => 'Queue Driver',
                    'queue_placeholder'        => 'Queue Driver',
                    'redis_label'              => 'Redis Driver',
                    'redis_host'               => 'Redis Host',
                    'redis_password'           => 'Redis Wachtwoord',
                    'redis_port'               => 'Redis Poort',

                    'mail_label'                  => 'Mail',
                    'mail_driver_label'           => 'Mail Driver',
                    'mail_driver_placeholder'     => 'Mail Driver',
                    'mail_host_label'             => 'Mail Host',
                    'mail_host_placeholder'       => 'Mail Host',
                    'mail_port_label'             => 'Mail Poort',
                    'mail_port_placeholder'       => 'Mail Poort',
                    'mail_username_label'         => 'Mail Gebruikersnaam',
                    'mail_username_placeholder'   => 'Mail Gebruikersnaam',
                    'mail_password_label'         => 'Mail Wachtwoord',
                    'mail_password_placeholder'   => 'Mail Wachtwoord',
                    'mail_encryption_label'       => 'Mail Encryptie',
                    'mail_encryption_placeholder' => 'Mail Encryptie',

                    'pusher_label'                  => 'Pusher',
                    'pusher_app_id_label'           => 'Pusher App Id',
                    'pusher_app_id_palceholder'     => 'Pusher App Id',
                    'pusher_app_key_label'          => 'Pusher App Key',
                    'pusher_app_key_palceholder'    => 'Pusher App Key',
                    'pusher_app_secret_label'       => 'Pusher App Secret',
                    'pusher_app_secret_palceholder' => 'Pusher App Secret',
                ],
                'buttons' => [
                    'setup_database'    => 'Setup Database',
                    'setup_application' => 'Setup Applicatie',
                    'install'           => 'Installeren',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'Stap 3 | Omgeving Instellingen  | Klassieke Editor',
            'title'         => 'Klassieke Omgevings Editor',
            'save'          => '.env opslaan',
            'back'          => 'Form Wizard gebruiken',
            'install'       => 'Opslaan en Installeren',
        ],
        'success' => 'Uw .env bestandsinstellingen zijn opgeslagen.',
        'errors'  => 'Fout bij opslaan .env bestand, aub handmatig aanmaken.',
    ],

    'install' => 'Installeren',

    /*
     *
     * Installed Log translations.
     *
     */
    'installed' => [
        'success_log_message' => 'FreeScout Installer was succesvol GEÏNSTALLEERD op ',
    ],

    /*
     *
     * Final page translations.
     *
     */
    'final' => [
        'title'         => 'Installatie Voltooid',
        'templateTitle' => 'Installatie Voltooid',
        'finished'      => 'Applicatie is succesvol geïnstalleerd.',
        'migration'     => 'Migratie &amp; Seed Console Output:',
        'console'       => 'Applicatie Console Output:',
        'log'           => 'Installatie Log Entry:',
        'env'           => 'Laatste .env Bestand:',
        'exit'          => 'Klik hier om af te sluiten',
    ],

    /*
     *
     * Update specific translations
     *
     */
    'updater' => [
        /*
         *
         * Shared translations.
         *
         */
        'title' => 'FreeScout Updater',

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'welcome' => [
            'title'   => 'Welkom bij de Updater',
            'message' => 'Welkom bij de update wizard.',
        ],

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'overview' => [
            'title'           => 'Overzicht',
            'message'         => 'Er is 1 update.|Er zijn :number updates.',
            'install_updates' => 'Updates Installeren',
        ],

        /*
         *
         * Final page translations.
         *
         */
        'final' => [
            'title'    => 'Voltooid',
            'finished' => 'Applicatie\'s database is succesvol bijgewerkt.',
            'exit'     => 'Klik hier om af te sluiten',
        ],

        'log' => [
            'success_message' => 'FreeScout Installer was succesvol GEUPDATED op ',
        ],
    ],
];
