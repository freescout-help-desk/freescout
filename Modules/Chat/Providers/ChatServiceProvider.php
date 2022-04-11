<?php

namespace Modules\Chat\Providers;

use App\Mailbox;
use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('CHAT_MODULE', 'chat');

class ChatServiceProvider extends ServiceProvider
{
    const CHANNEL = 1;

    /**
     * List of mailboxes by ids.
     */
    public static $mailboxes_ids = [];

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(CHAT_MODULE).'/css/module.css';
            $styles[] = \Module::getPublicPath(CHAT_MODULE).'/css/bootstrap-colorpicker.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            //$javascripts[] = \Module::getPublicPath(TIMETR_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(CHAT_MODULE).'/js/bootstrap-colorpicker.js';
            $javascripts[] = \Module::getPublicPath(CHAT_MODULE).'/js/module.js';
            return $javascripts;
        });
        
        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('chat::admin/partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 70);

        // Clear cache.
        \Eventy::addAction('conversation.user_replied_can_undo', function($conversation, $thread) {
            $thread_ids = Thread::where('conversation_id', $conversation->id)->pluck('id');

            foreach ($thread_ids as $thread_id) {
                \Cache::forget('chat.threads_'.$thread_id);
            }
        }, 20, 2);

        \Eventy::addFilter('channel.name', function($name, $channel) {
            if ($name) {
                return $name;
            }
            if ($channel == self::CHANNEL) {
                return __('Chat');
            } else {
                return $name;
            }
        }, 20, 2);
    }

    /**
     * Returns a shorter value than encrypt().
     */
    public static function encodeMailboxId($id)
    {
        return crc32(config('app.key').'chat'.$id);
    }

    public static function decodeMailboxId($encoded_id, $extra_salt = '')
    {
        if (!empty(self::$mailboxes_ids[$encoded_id])) {
            return self::$mailboxes_ids[$encoded_id];
        }

        $mailboxes = Mailbox::get();

        foreach ($mailboxes as $mailbox) {
            $cur_encoded_id = self::encodeMailboxId($mailbox->id);
            
            if ($cur_encoded_id == $encoded_id) {

                self::$mailboxes_ids[$cur_encoded_id] = $mailbox;
                return $mailbox;
            }
        }

        return null;
    }

    public static function encryptId($id)
    {
        return encrypt($id);
    }

    public static function decryptId($encrypted_id)
    {
        try {
            return decrypt($encrypted_id);
        } catch (\Exception $e) {
            \Helper::logException($e);
        }
        return null;
    }

    public static function getWidgetScriptUrl($mailbox_id, $include_version = false)
    {
        $url = config('app.url').\Module::getPublicPath(CHAT_MODULE).'/js/widget.js';

        if ($include_version) {
            $module = \Module::findByAlias(CHAT_MODULE);
            if ($module) {
                $url .= '?v='.substr(crc32($module->get('version').config('app.key')), 0, 4);
            }
        }

        return $url;
    }

    public static function saveWidgetSettings($mailbox_id, $settings)
    {
        return \Option::set(CHAT_MODULE.'.widget_settings_'.$mailbox_id, $settings);
    }  

    public static function getWidgetSettings($mailbox_id)
    {
        return \Option::get(CHAT_MODULE.'.widget_settings_'.$mailbox_id, []);
    }

    public static function getDefaultWidgetSettings()
    {
        return [
            'id' => '',
            'color' => '#0068bd',
            //'title' => __('Chat with us'),
            'position' => 'br',
            'locale' => '',
            'visitor_name' => '',
            'visitor_email' => '',
            'visitor_phone' => '',
        ];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('chat.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'chat'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/chat');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/chat';
        }, \Config::get('view.paths')), [$sourcePath]), 'chat');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
