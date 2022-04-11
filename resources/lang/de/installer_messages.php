<?php

return [

    /*
     *
     * Shared translations.
     *
     */
    'title'  => 'FreeScout Installer',
    'next'   => 'Nächster Schritt',
    'back'   => 'Vorheriger Schritt',
    'finish' => 'Installieren',
    'forms'  => [
        'errorTitle' => 'Es ist zu folgenden Fehlern gekommen:',
    ],

    /*
     *
     * Home page translations.
     *
     */
    'welcome' => [
        'templateTitle' => 'Willkommen',
        'title'         => 'FreeScout Installer',
        'message'       => 'Einfache Installation und Setup Wizard.',
        'next'          => 'Prüfe Vorraussetzungen',
    ],

    /*
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'templateTitle' => 'Schritt 1 | Server Vorraussetzungen',
        'title'         => 'Server Vorraussetzungen',
        'next'          => 'Prüfe Berechtigungen',
    ],

    /*
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'templateTitle' => 'Schritt 2 | Berechtigungen',
        'title'         => 'Berechtigungen',
        'next'          => 'Umgebung konfigurieren',
    ],

    /*
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle'  => 'Schritt 3 | Umgebungseinstellungen',
            'title'          => 'Umgebungseinstellungen',
            'desc'           => 'Bitte wählen wie die <code>.env</code> Datei der Anwendung konfiguriert werden soll.',
            'wizard-button'  => 'Formular Wizard Setup',
            'classic-button' => 'Klassischer Text Editor',
        ],
        'wizard' => [
            'templateTitle' => 'Schritt 3 | Umgebungseinstellungen | geführter Wizard',
            'title'         => 'Geführter <code>.env</code> Wizard',
            'tabs'          => [
                'environment' => 'Umgebung',
                'database'    => 'Datenbank',
                'application' => 'Anwendung',
            ],
            'form' => [
                'name_required'                      => 'Ein Umgebungsname wird benötigt.',
                'app_name_label'                     => 'Anwendungsname',
                'app_name_placeholder'               => 'Name der Anwendung',
                'app_environment_label'              => 'Anwendungsumgebung',
                'app_environment_label_local'        => 'Lokal',
                'app_environment_label_developement' => 'Entwicklung',
                'app_environment_label_qa'           => 'Qa',
                'app_environment_label_production'   => 'Produktiv',
                'app_environment_label_other'        => 'Anderes',
                'app_environment_placeholder_other'  => 'Umge eingeben...',
                'app_debug_label'                    => 'Anwendung Debuggen',
                'app_debug_label_true'               => 'Wahr',
                'app_debug_label_false'              => 'Falsch',
                'app_log_level_label'                => 'Anwendungs Log Level',
                'app_log_level_label_debug'          => 'debug',
                'app_log_level_label_info'           => 'Info',
                'app_log_level_label_notice'         => 'Notiz',
                'app_log_level_label_warning'        => 'Warnung',
                'app_log_level_label_error'          => 'Fehler',
                'app_log_level_label_critical'       => 'Kritisch',
                'app_log_level_label_alert'          => 'Warnung',
                'app_log_level_label_emergency'      => 'Notfall',
                'app_url_label'                      => 'Anwendungs URL',
                'app_url_placeholder'                => 'URL der Anwendung',
                'db_connection_label'                => 'Datenbankverbindung',
                'db_connection_label_mysql'          => 'mysql',
                'db_connection_label_sqlite'         => 'sqlite',
                'db_connection_label_pgsql'          => 'pgsql',
                'db_connection_label_sqlsrv'         => 'sqlsrv',
                'db_host_label'                      => 'Datenbank Host',
                'db_host_placeholder'                => 'Host-Adresse der Datenbank',
                'db_port_label'                      => 'Datenbank Port',
                'db_port_placeholder'                => 'Port der Datenbank',
                'db_name_label'                      => 'Datenbank Name',
                'db_name_placeholder'                => 'Name der Datenbank',
                'db_username_label'                  => 'Datenbank Benutzername',
                'db_username_placeholder'            => 'Name des Benutzers der Datenbank',
                'db_password_label'                  => 'Datenbank Passwort',
                'db_password_placeholder'            => 'Passwort des Benutzers der Datenbank',

                'app_tabs' => [
                    'more_info'                => 'Mehr Info',
                    'broadcasting_title'       => 'Broadcasting, Caching, Session, &amp; Queue',
                    'broadcasting_label'       => 'Broadcast Treiber',
                    'broadcasting_placeholder' => 'Broadcast Treiber',
                    'cache_label'              => 'Cache Treiber',
                    'cache_placeholder'        => 'Cache Treiber',
                    'session_label'            => 'Session Treiber',
                    'session_placeholder'      => 'Session Treiber',
                    'queue_label'              => 'Queue Treiber',
                    'queue_placeholder'        => 'Queue Treiber',
                    'redis_label'              => 'Redis Treiber',
                    'redis_host'               => 'Redis Host',
                    'redis_password'           => 'Redis Passwort',
                    'redis_port'               => 'Redis Port',

                    'mail_label'                  => 'Email',
                    'mail_driver_label'           => 'Email Treiber',
                    'mail_driver_placeholder'     => 'Email Treiber',
                    'mail_host_label'             => 'Email Host',
                    'mail_host_placeholder'       => 'Email Host',
                    'mail_port_label'             => 'Email Port',
                    'mail_port_placeholder'       => 'Email Port',
                    'mail_username_label'         => 'Email Benutzername',
                    'mail_username_placeholder'   => 'Email Benutzername',
                    'mail_password_label'         => 'Email Passwort',
                    'mail_password_placeholder'   => 'Email Passwort',
                    'mail_encryption_label'       => 'Email Verschlüsselung',
                    'mail_encryption_placeholder' => 'Email Verschlüsselung',

                    'pusher_label'                  => 'Pusher',
                    'pusher_app_id_label'           => 'Pusher Anwendungs Id',
                    'pusher_app_id_palceholder'     => 'Pusher Anwendungs Id',
                    'pusher_app_key_label'          => 'Pusher Anwendungs Key',
                    'pusher_app_key_palceholder'    => 'Pusher Anwendungs Key',
                    'pusher_app_secret_label'       => 'Pusher Anwendungs Secret',
                    'pusher_app_secret_palceholder' => 'Pusher Anwendungs Secret',
                ],
                'buttons' => [
                    'setup_database'    => 'Datenbank einrichten',
                    'setup_application' => 'Anwendung einrichten',
                    'install'           => 'Installieren',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'Schritt 3 | Umgebungseinstellungen | Klassischer Editor',
            'title'         => 'Klassischer Editor für Umgebungen',
            'save'          => 'Speichere .env',
            'back'          => 'Benutze Formular Wizard',
            'install'       => 'Speichern und Installieren',
        ],
        'success' => 'Die .env Einstellungen wurden gespeichert.',
        'errors'  => 'Fehler beim Speichern der .env Datei. Bitte manuell erstellen.',
    ],

    'install' => 'Installieren',

    /*
     *
     * Installed Log translations.
     *
     */
    'installed' => [
        'success_log_message' => 'FreeScout Installer erfolgreich installiert am ',
    ],

    /*
     *
     * Final page translations.
     *
     */
    'final' => [
        'title'         => 'Installation Fertig',
        'templateTitle' => 'Installation Fertig',
        'finished'      => 'Die Anwendung wurde erfolgreich aktualisiert.',
        'migration'     => 'Migrations- &amp; Seed-Konsolenausgabe:',
        'console'       => 'Anwendungskonsolenausgabe:',
        'log'           => 'Installations Log Eintrag:',
        'env'           => 'Endgültige .env Datei:',
        'exit'          => 'Hier klicken zum Verlassen',
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
            'title'   => 'Willkommen beim Updater',
            'message' => 'Willkommen beim Update Wizard.',
        ],

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'overview' => [
            'title'           => 'Überblick',
            'message'         => 'Es gibt 1 Aktualisierung.|Es gibt :number Aktualisierungen.',
            'install_updates' => 'Installiere Aktualisierungen',
        ],

        /*
         *
         * Final page translations.
         *
         */
        'final' => [
            'title'    => 'Fertig',
            'finished' => 'Die Datenbank der Anwendung wurde erfolgreich aktualisiert.',
            'exit'     => 'Hier klicken zum Verlassen',
        ],

        'log' => [
            'success_message' => 'FreeScout Installer erfolgreich aktualisiert am ',
        ],
    ],
];