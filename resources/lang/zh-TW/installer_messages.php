<?php

return [

    /*
     *
     * Shared translations.
     *
     */
    'title'  => 'FreeScout 安裝程式',
    'next'   => '下一步',
    'back'   => '上一步',
    'finish' => '安裝',
    'forms'  => [
        'errorTitle' => '發生以下錯誤：',
    ],

    /*
     *
     * Home page translations.
     *
     */
    'welcome' => [
        'templateTitle' => '歡迎使用',
        'title'          => 'FreeScout 安裝程式',
        'message'        => '簡單的安裝與設定精靈。',
        'next'           => '檢查伺服器環境',
    ],

    /*
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'templateTitle' => '第 1 步 | 伺服器需求',
        'title'          => '伺服器需求',
        'next'           => '檢查檔案權限',
    ],

    /*
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'templateTitle' => '第 2 步 | 檔案權限',
        'title'          => '檔案權限',
        'next'           => '設定環境變數',
    ],

    /*
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle'  => '第 3 步 | 環境設定',
            'title'          => '環境設定',
            'desc'           => '請選擇您要如何設定應用程式的 <code>.env</code> 檔案。',
            'wizard-button'  => '使用設定精靈',
            'classic-button' => '使用文字編輯器',
        ],
        'wizard' => [
            'templateTitle' => '第 3 步 | 環境設定 | 引導精靈',
            'title'          => '<code>.env</code> 引導精靈',
            'tabs'          => [
                'environment' => '環境環境',
                'database'    => '資料庫',
                'application' => '應用程式',
            ],
            'form' => [
                'name_required'                      => '必須輸入環境名稱。',
                'app_name_label'                     => '應用程式名稱',
                'app_name_placeholder'               => '應用程式名稱',
                'app_environment_label'              => '應用程式環境',
                'app_environment_label_local'        => '本地 (Local)',
                'app_environment_label_developement' => '開發 (Development)',
                'app_environment_label_qa'           => '測試 (QA)',
                'app_environment_label_production'   => '正式 (Production)',
                'app_environment_label_other'        => '其他',
                'app_environment_placeholder_other'  => '輸入您的環境...',
                'app_debug_label'                    => '除錯模式 (Debug)',
                'app_debug_label_true'               => '開啟 (True)',
                'app_debug_label_false'              => '關閉 (False)',
                'app_log_level_label'                => '日誌層級',
                'app_log_level_label_debug'          => 'debug',
                'app_log_level_label_info'           => 'info',
                'app_log_level_label_notice'         => 'notice',
                'app_log_level_label_warning'        => 'warning',
                'app_log_level_label_error'          => 'error',
                'app_log_level_label_critical'       => 'critical',
                'app_log_level_label_alert'          => 'alert',
                'app_log_level_label_emergency'      => 'emergency',
                'app_url_label'                      => '應用程式網址 (App URL)',
                'app_url_placeholder'                => '應用程式網址',
                'db_connection_label'                => '資料庫連線方式',
                'db_connection_label_mysql'          => 'mysql',
                'db_connection_label_sqlite'         => 'sqlite',
                'db_connection_label_pgsql'          => 'pgsql',
                'db_connection_label_sqlsrv'         => 'sqlsrv',
                'db_host_label'                      => '資料庫主機 (Host)',
                'db_host_placeholder'                => '資料庫主機',
                'db_port_label'                      => '資料庫埠號 (Port)',
                'db_port_placeholder'                => '資料庫埠號',
                'db_name_label'                      => '資料庫名稱',
                'db_name_placeholder'                => '資料庫名稱',
                'db_username_label'                  => '資料庫帳號',
                'db_username_placeholder'            => '資料庫帳號',
                'db_password_label'                  => '資料庫密碼',
                'db_password_placeholder'            => '資料庫密碼',

                'app_tabs' => [
                    'more_info'                => '更多資訊',
                    'broadcasting_title'       => '廣播、快取、工作階段與隊列 (Broadcasting, Caching, Session, &amp; Queue)',
                    'broadcasting_label'       => '廣播驅動 (Broadcast Driver)',
                    'broadcasting_placeholder' => '廣播驅動',
                    'cache_label'              => '快取驅動 (Cache Driver)',
                    'cache_placeholder'        => '快取驅動',
                    'session_label'            => '工作階段驅動 (Session Driver)',
                    'session_placeholder'      => '工作階段驅動',
                    'queue_label'              => '隊列驅動 (Queue Driver)',
                    'queue_placeholder'        => '隊列驅動',
                    'redis_label'              => 'Redis 驅動',
                    'redis_host'               => 'Redis 主機',
                    'redis_password'           => 'Redis 密碼',
                    'redis_port'               => 'Redis 埠號',

                    'mail_label'                  => '郵件 (Mail)',
                    'mail_driver_label'           => '郵件驅動 (Mail Driver)',
                    'mail_driver_placeholder'     => '郵件驅動',
                    'mail_host_label'             => '郵件主機 (Mail Host)',
                    'mail_host_placeholder'       => '郵件主機',
                    'mail_port_label'             => '郵件埠號 (Mail Port)',
                    'mail_port_placeholder'       => '郵件埠號',
                    'mail_username_label'         => '郵件帳號',
                    'mail_username_placeholder'   => '郵件帳號',
                    'mail_password_label'         => '郵件密碼',
                    'mail_password_placeholder'   => '郵件密碼',
                    'mail_encryption_label'       => '郵件加密方式',
                    'mail_encryption_placeholder' => '郵件加密方式',

                    'pusher_label'                  => 'Pusher',
                    'pusher_app_id_label'           => 'Pusher App Id',
                    'pusher_app_id_palceholder'     => 'Pusher App Id',
                    'pusher_app_key_label'          => 'Pusher App Key',
                    'pusher_app_key_palceholder'    => 'Pusher App Key',
                    'pusher_app_secret_label'       => 'Pusher App Secret',
                    'pusher_app_secret_palceholder' => 'Pusher App Secret',
                ],
                'buttons' => [
                    'setup_database'    => '設定資料庫',
                    'setup_application' => '設定應用程式',
                    'install'           => '安裝',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => '第 3 步 | 環境設定 | 文字編輯器',
            'title'          => '傳統環境編輯器',
            'save'           => '儲存 .env',
            'back'           => '使用設定精靈',
            'install'        => '儲存並安裝',
        ],
        'success' => '您的 .env 檔案設定已儲存。',
        'errors'  => '無法儲存 .env 檔案，請手動建立。',
    ],

    'install' => '安裝',

    /*
     *
     * Installed Log translations.
     *
     */
    'installed' => [
        'success_log_message' => 'FreeScout 安裝程式成功安裝於 ',
    ],

    /*
     *
     * Final page translations.
     *
     */
    'final' => [
        'title'          => '安裝完成',
        'templateTitle' => '安裝完成',
        'finished'      => '應用程式已成功安裝。',
        'migration'     => '資料庫遷移與種子資料輸出：',
        'console'       => '應用程式控制台輸出：',
        'log'           => '安裝日誌紀錄：',
        'env'           => '最終產生的 .env 檔案：',
        'exit'          => '點擊此處退出',
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
        'title' => 'FreeScout 更新程式',

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'welcome' => [
            'title'   => '歡迎使用更新程式',
            'message' => '歡迎使用更新精靈。',
        ],

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'overview' => [
            'title'           => '概覽',
            'message'          => '目前有 1 個更新。|目前有 :number 個更新。',
            'install_updates' => '安裝更新',
        ],

        /*
         *
         * Final page translations.
         *
         */
        'final' => [
            'title'    => '完成',
            'finished' => '應用程式資料庫已成功更新。',
            'exit'     => '點擊此處退出',
        ],

        'log' => [
            'success_message' => 'FreeScout 安裝程式成功更新於 ',
        ],
    ],
];
