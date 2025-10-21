<?php

return [

    /*
     *
     * Shared translations.
     * תרגומים משותפים.
     *
     */
    'title'  => 'מתקין FreeScout',
    'next'   => 'השלב הבא',
    'back'   => 'הקודם',
    'finish' => 'התקן',
    'forms'  => [
        'errorTitle' => 'אירעו השגיאות הבאות:',
    ],

    /*
     *
     * Home page translations.
     * תרגומים של דף הבית.
     *
     */
    'welcome' => [
        'templateTitle' => 'ברוכים הבאים',
        'title'         => 'מתקין FreeScout',
        'message'       => 'אשף התקנה והגדרה קל ופשוט.',
        'next'          => 'בדיקת דרישות',
    ],

    /*
     *
     * Requirements page translations.
     * תרגומים של דף הדרישות.
     *
     */
    'requirements' => [
        'templateTitle' => 'שלב 1 | דרישות שרת',
        'title'         => 'דרישות שרת',
        'next'          => 'בדיקת הרשאות',
    ],

    /*
     *
     * Permissions page translations.
     * תרגומים של דף ההרשאות.
     *
     */
    'permissions' => [
        'templateTitle' => 'שלב 2 | הרשאות',
        'title'         => 'הרשאות',
        'next'          => 'הגדרת סביבה',
    ],

    /*
     *
     * Environment page translations.
     * תרגומים של דף הסביבה.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle'  => 'שלב 3 | הגדרות סביבה',
            'title'          => 'הגדרות סביבה',
            'desc'           => 'אנא בחר כיצד ברצונך להגדיר את קובץ ה-<code>.env</code> של האפליקציה.',
            'wizard-button'  => 'הגדרת טופס באשף',
            'classic-button' => 'עורך טקסט קלאסי',
        ],
        'wizard' => [
            'templateTitle' => 'שלב 3 | הגדרות סביבה | אשף מודרך',
            'title'         => 'אשף <code>.env</code> מודרך',
            'tabs'          => [
                'environment' => 'סביבה',
                'database'    => 'מסד נתונים',
                'application' => 'אפליקציה',
            ],
            'form' => [
                'name_required'                      => 'נדרש שם סביבה.',
                'app_name_label'                     => 'שם האפליקציה',
                'app_name_placeholder'               => 'שם האפליקציה',
                'app_environment_label'              => 'סביבת האפליקציה',
                'app_environment_label_local'        => 'מקומית (Local)',
                'app_environment_label_developement' => 'פיתוח (Development)',
                'app_environment_label_qa'           => 'בדיקות (QA)',
                'app_environment_label_production'   => 'ייצור (Production)',
                'app_environment_label_other'        => 'אחר',
                'app_environment_placeholder_other'  => 'הזן את סביבתך...',
                'app_debug_label'                    => 'ניפוי באגים (Debug)',
                'app_debug_label_true'               => 'פעיל',
                'app_debug_label_false'              => 'כבוי',
                'app_log_level_label'                => 'רמת רישום לוגים',
                'app_log_level_label_debug'          => 'debug',
                'app_log_level_label_info'           => 'info',
                'app_log_level_label_notice'         => 'notice',
                'app_log_level_label_warning'        => 'warning',
                'app_log_level_label_error'          => 'error',
                'app_log_level_label_critical'       => 'critical',
                'app_log_level_label_alert'          => 'alert',
                'app_log_level_label_emergency'      => 'emergency',
                'app_url_label'                      => 'כתובת האפליקציה (URL)',
                'app_url_placeholder'                => 'כתובת האפליקציה',
                'db_connection_label'                => 'חיבור מסד נתונים',
                'db_connection_label_mysql'          => 'mysql',
                'db_connection_label_sqlite'         => 'sqlite',
                'db_connection_label_pgsql'          => 'pgsql',
                'db_connection_label_sqlsrv'         => 'sqlsrv',
                'db_host_label'                      => 'מארח מסד נתונים',
                'db_host_placeholder'                => 'מארח מסד נתונים',
                'db_port_label'                      => 'פורט מסד נתונים',
                'db_port_placeholder'                => 'פורט מסד נתונים',
                'db_name_label'                      => 'שם מסד נתונים',
                'db_name_placeholder'                => 'שם מסד נתונים',
                'db_username_label'                  => 'שם משתמש למסד נתונים',
                'db_username_placeholder'            => 'שם משתמש למסד נתונים',
                'db_password_label'                  => 'סיסמה למסד נתונים',
                'db_password_placeholder'            => 'סיסמה למסד נתונים',

                'app_tabs' => [
                    'more_info'                => 'מידע נוסף',
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
                    'redis_password'           => 'Redis Password',
                    'redis_port'               => 'Redis Port',

                    'mail_label'                  => 'דואר',
                    'mail_driver_label'           => 'Mail Driver',
                    'mail_driver_placeholder'     => 'Mail Driver',
                    'mail_host_label'             => 'Mail Host',
                    'mail_host_placeholder'       => 'Mail Host',
                    'mail_port_label'             => 'Mail Port',
                    'mail_port_placeholder'       => 'Mail Port',
                    'mail_username_label'         => 'Mail Username',
                    'mail_username_placeholder'   => 'Mail Username',
                    'mail_password_label'         => 'Mail Password',
                    'mail_password_placeholder'   => 'Mail Password',
                    'mail_encryption_label'       => 'Mail Encryption',
                    'mail_encryption_placeholder' => 'Mail Encryption',

                    'pusher_label'                  => 'Pusher',
                    'pusher_app_id_label'           => 'Pusher App Id',
                    'pusher_app_id_palceholder'     => 'Pusher App Id',
                    'pusher_app_key_label'          => 'Pusher App Key',
                    'pusher_app_key_palceholder'    => 'Pusher App Key',
                    'pusher_app_secret_label'       => 'Pusher App Secret',
                    'pusher_app_secret_palceholder' => 'Pusher App Secret',
                ],
                'buttons' => [
                    'setup_database'    => 'הגדר מסד נתונים',
                    'setup_application' => 'הגדר אפליקציה',
                    'install'           => 'התקן',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'שלב 3 | הגדרות סביבה | עורך קלאסי',
            'title'         => 'עורך סביבה קלאסי',
            'save'          => 'שמור .env',
            'back'          => 'השתמש באשף הטפסים',
            'install'       => 'שמור והתקן',
        ],
        'success' => 'הגדרות קובץ ה-.env שלך נשמרו בהצלחה.',
        'errors'  => 'לא ניתן לשמור את קובץ ה-.env, אנא צור אותו ידנית.',
    ],

    'install' => 'התקן',

    /*
     *
     * Installed Log translations.
     * תרגומי לוג התקנה.
     *
     */
    'installed' => [
        'success_log_message' => 'מתקין FreeScout הותקן בהצלחה ב- ',
    ],

    /*
     *
     * Final page translations.
     * תרגומים של הדף הסופי.
     *
     */
    'final' => [
        'title'         => 'ההתקנה הסתיימה',
        'templateTitle' => 'ההתקנה הסתיימה',
        'finished'      => 'האפליקציה הותקנה בהצלחה.',
        'migration'     => 'פלט קונסולת Migration &amp; Seed:',
        'console'       => 'פלט קונסולת האפליקציה:',
        'log'           => 'רשומת לוג התקנה:',
        'env'           => 'קובץ .env סופי:',
        'exit'          => 'לחץ כאן ליציאה',
    ],

    /*
     *
     * Update specific translations
     * תרגומים ספציפיים לעדכון
     *
     */
    'updater' => [
        /*
         *
         * Shared translations.
         * תרגומים משותפים.
         *
         */
        'title' => 'מעדכן FreeScout',

        /*
         *
         * Welcome page translations for update feature.
         * תרגומים של דף ברוכים הבאים עבור תכונת העדכון.
         *
         */
        'welcome' => [
            'title'   => 'ברוכים הבאים למעדכן',
            'message' => 'ברוכים הבאים לאשף העדכון.',
        ],

        /*
         *
         * Welcome page translations for update feature.
         * תרגומים של דף ברוכים הבאים עבור תכונת העדכון.
         *
         */
        'overview' => [
            'title'           => 'סקירה כללית',
            'message'         => 'קיים עדכון אחד.|קיימים :number עדכונים.',
            'install_updates' => 'התקן עדכונים',
        ],

        /*
         *
         * Final page translations.
         * תרגומים של הדף הסופי.
         *
         */
        'final' => [
            'title'    => 'הסתיים',
            'finished' => 'מסד הנתונים של האפליקציה עודכן בהצלחה.',
            'exit'     => 'לחץ כאן ליציאה',
        ],

        'log' => [
            'success_message' => 'מתקין FreeScout עודכן בהצלחה ב- ',
        ],
    ],
];