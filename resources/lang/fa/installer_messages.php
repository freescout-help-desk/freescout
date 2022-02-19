<?php

return [

    /*
     *
     * Shared translations.
     *
     */
    'title'  => 'نصب کننده FreeScout',
    'next'   => 'مرحله بعدی',
    'back'   => 'مرحله قبلی',
    'finish' => 'نصب',
    'forms'  => [
        'errorTitle' => 'خطا های زیر رخ داده است:',
    ],

    /*
     *
     * Home page translations.
     *
     */
    'welcome' => [
        'templateTitle' => 'خوش امدید',
        'title'         => 'نصب کننده FreeScout',
        'message'       => 'نصب و راه اندازی آسان',
        'next'          => 'بررسی الزامات',
    ],

    /*
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'templateTitle' => 'مرحله 1 | نیازمندی های سرور',
        'title'         => 'نیازمندی های سرور',
        'next'          => 'مجوز ها را بررسی کنید',
    ],

    /*
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'templateTitle' => 'مرحله 2 | مجوزها',
        'title'         => 'مجوز ها',
        'next'          => 'پیکربندی محیط',
    ],

    /*
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle'  => 'مرحله 3 | تنظیمات محیطی',
            'title'          => 'تنظیمات محیطی',
            'desc'           => 'لطفاً نحوه پیکربندی فایل <code>.env</code> برنامه ها را انتخاب کنید.',
            'wizard-button'  => 'تنظیم Form Wizard ',
            'classic-button' => 'ویرایشگر متن کلاسیک',
        ],
        'wizard' => [
            'templateTitle' => 'مرحله 3 | تنظیمات محیط | Wizard هدایت شده',
            'title'         => 'راهنمای <code>.env</code> Wizard',
            'tabs'          => [
                'environment' => 'محیط',
                'database'    => 'پایگاه داده',
                'application' => 'کاربرد',
            ],
            'form' => [
                'name_required'                      => 'نام محیط مورد نیاز است.',
                'app_name_label'                     => 'نام برنامه',
                'app_name_placeholder'               => 'نام برنامه',
                'app_environment_label'              => 'محیط برنامه',
                'app_environment_label_local'        => 'محلی',
                'app_environment_label_developement' => 'توسعه',
                'app_environment_label_qa'           => 'Qa',
                'app_environment_label_production'   => 'تولید',
                'app_environment_label_other'        => 'دیگر',
                'app_environment_placeholder_other'  => 'وارد محیط خود شوید...',
                'app_debug_label'                    => 'خطایابی برنامه',
                'app_debug_label_true'               => 'صحیح',
                'app_debug_label_false'              => 'نادرست',
                'app_log_level_label'                => 'سطح گزارش برنامه',
                'app_log_level_label_debug'          => 'اشکال زدایی',
                'app_log_level_label_info'           => 'اطلاعات',
                'app_log_level_label_notice'         => 'اطلاع',
                'app_log_level_label_warning'        => 'هشدار',
                'app_log_level_label_error'          => 'خطا',
                'app_log_level_label_critical'       => 'بحرانی',
                'app_log_level_label_alert'          => 'هشدار',
                'app_log_level_label_emergency'      => 'اضطراری',
                'app_url_label'                      => 'آدرس برنامه',
                'app_url_placeholder'                => 'آدرس برنامه',
                'db_connection_label'                => 'اتصال به پایگاه داده',
                'db_connection_label_mysql'          => 'mysql',
                'db_connection_label_sqlite'         => 'sqlite',
                'db_connection_label_pgsql'          => 'pgsql',
                'db_connection_label_sqlsrv'         => 'sqlsrv',
                'db_host_label'                      => 'Database Host',
                'db_host_placeholder'                => 'Database Host',
                'db_port_label'                      => 'Database Port',
                'db_port_placeholder'                => 'Database Port',
                'db_name_label'                      => 'Database Name',
                'db_name_placeholder'                => 'Database Name',
                'db_username_label'                  => 'Database User Name',
                'db_username_placeholder'            => 'Database User Name',
                'db_password_label'                  => 'Database Password',
                'db_password_placeholder'            => 'Database Password',

                'app_tabs' => [
                    'more_info'                => 'اطلاعات بیشتر',
                    'broadcasting_title'       => 'پخش، ذخیره سازی، جلسه، و amp; صف',
                    'broadcasting_label'       => 'درایور پخش',
                    'broadcasting_placeholder' => 'درایور پخش',
                    'cache_label'              => 'درایور کش',
                    'cache_placeholder'        => 'درایور کش',
                    'session_label'            => 'درایور جلسه',
                    'session_placeholder'      => 'درایور جلسه',
                    'queue_label'              => 'درایور صف',
                    'queue_placeholder'        => 'درایور صف',
                    'redis_label'              => 'Redis Driver',
                    'redis_host'               => 'Redis Host',
                    'redis_password'           => 'Redis Password',
                    'redis_port'               => 'Redis Port',

                    'mail_label'                  => 'ایمیل',
                    'mail_driver_label'           => 'درایور ایمیل',
                    'mail_driver_placeholder'     => 'درایور ایمیل',
                    'mail_host_label'             => 'Mail Host',
                    'mail_host_placeholder'       => 'Mail Host',
                    'mail_port_label'             => 'Mail Port',
                    'mail_port_placeholder'       => 'Mail Port',
                    'mail_username_label'         => 'نام کاربری ایمیل',
                    'mail_username_placeholder'   => 'نام کاربری ایمیل',
                    'mail_password_label'         => 'رمز عبور ایمیل',
                    'mail_password_placeholder'   => 'رمز عبور ایمیل',
                    'mail_encryption_label'       => 'رمزگذاری ایمیل',
                    'mail_encryption_placeholder' => 'رمزگذاری ایمیل',

                    'pusher_label'                  => 'هل دهنده',
                    'pusher_app_id_label'           => 'Pusher App Id',
                    'pusher_app_id_palceholder'     => 'Pusher App Id',
                    'pusher_app_key_label'          => 'Pusher App Key',
                    'pusher_app_key_palceholder'    => 'Pusher App Key',
                    'pusher_app_secret_label'       => 'Pusher App Secret',
                    'pusher_app_secret_palceholder' => 'Pusher App Secret',
                ],
                'buttons' => [
                    'setup_database'    => 'راه اندازی پایگاه داده',
                    'setup_application' => 'برنامه راه اندازی',
                    'install'           => 'نصب',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'مرحله 3 | تنظیمات محیط | ویرایشگر کلاسیک',
            'title'         => 'ویرایشگر محیط کلاسیک',
            'save'          => 'ذخیره .env',
            'back'          => 'از Form Wizard استفاده کنید',
            'install'       => 'ذخیره و نصب کنید',
        ],
        'success' => 'تنظیمات فایل .env شما ذخیره شده است.',
        'errors'  => 'فایل env. ذخیره نمی شود، لطفاً آن را به صورت دستی ایجاد کنید.',
    ],

    'install' => 'نصب',

    /*
     *
     * Installed Log translations.
     *
     */
    'installed' => [
        'success_log_message' => 'نصب کننده FreeScout با موفقیت نصب شد',
    ],

    /*
     *
     * Final page translations.
     *
     */
    'final' => [
        'title'         => 'نصب به پایان رسید',
        'templateTitle' => 'نصب به پایان رسید',
        'finished'      => 'برنامه با موفقیت نصب شد',
        'migration'     => 'Migration &amp; Seed کنسول خارجی:',
        'console'       => 'خروجی کنسول برنامه:',
        'log'           => 'ثبت گزارش نصب:',
        'env'           => 'فایل نهایی .env:',
        'exit'          => 'برای خروج اینجا را کلیک کنید',
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
        'title' => 'به روز رسانی FreeScout',

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'welcome' => [
            'title'   => 'به The Updater خوش آمدید',
            'message' => 'به update wizard خوش آمدید.',
        ],

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'overview' => [
            'title'           => 'بررسی اجمالی',
            'message'         => '1 به روز رسانی وجود دارد.|به روز رسانی :number وجود دارد.',
            'install_updates' => 'به روز رسانی ها را نصب کن',
        ],

        /*
         *
         * Final page translations.
         *
         */
        'final' => [
            'title'    => 'تمام شده',
            'finished' => 'پایگاه داده برنامه با موفقیت به روز شد.',
            'exit'     => 'برای خروج اینجا را کلیک کنید',
        ],

        'log' => [
            'success_message' => 'نصب کننده FreeScout با موفقیت به روز شد',
        ],
    ],
];

