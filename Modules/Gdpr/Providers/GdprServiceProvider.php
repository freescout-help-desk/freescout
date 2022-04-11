<?php

namespace Modules\Gdpr\Providers;

use App\Conversation;
use App\Customer;
use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('GDPR_MODULE', 'gdpr');

class GdprServiceProvider extends ServiceProvider
{
    // User permission.
    const PERM_DELETE_USERS = 20;

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
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(GDPR_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(GDPR_MODULE).'/js/module.js';
            return $javascripts;
        });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections[GDPR_MODULE] = ['title' => 'GDPR', 'icon' => 'certificate', 'order' => 700];

            return $sections;
        }, 50); 

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != GDPR_MODULE) {
                return $settings;
            }
           
            $settings['gdpr.delete_emails'] = config('gdpr.delete_emails');

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != GDPR_MODULE) {
                return $params;
            }

            $params = [
                'settings' => [
                    'gdpr.delete_emails' => [
                        'env' => 'GDPR_DELETE_EMAILS',
                    ],
                ]
            ];

            return $params;
        }, 20, 2);

        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != GDPR_MODULE) {
                return $view;
            } else {
                return 'gdpr::settings';
            }
        }, 20, 2);

        \Eventy::addFilter('customer.profile_menu', function($html, $customer) {
            if (!self::canDeleteUsers()) {
                return $html;
            }
            $html .= \View::make('gdpr::partials/customer_profile_menu', [
                'customer' => $customer,
            ])->render();

            return $html;
        }, 200, 2);

        \Eventy::addAction('gdpr.delete_customer_conversations', function($customer_id) {

            $conversations = [];
            $delete_emails = config('gdpr.delete_emails');
            do {
                $conversations = Conversation::where('customer_id', $customer_id)
                    ->limit(100)
                    ->get();

                // Delete from mail server.
                if ($delete_emails) {

                    foreach ($conversations as $conversation) {

                        if ($conversation->type != Conversation::TYPE_EMAIL) {
                            continue;
                        }

                        $message_ids = Thread::where('conversation_id', $conversation->id)
                            ->where('type', Thread::TYPE_CUSTOMER)
                            ->pluck('message_id')
                            ->toArray();

                        foreach ($message_ids as $message_id) {

                            $email_message = \MailHelper::fetchMessage($conversation->mailbox, $message_id);
                            if ($email_message) {
                                $email_message->delete();
                            }
                        }
                    }
                }

                // Delete from DB.
                Conversation::deleteConversationsForever($conversations->pluck('id'));
            } while (count($conversations));

            // Now delete the customer.
            $customer = Customer::find($customer_id);

            if ($customer) {
                $customer->delete();
            }
        });

        \Eventy::addFilter('user_permissions.list', function($list) {
            $list[] = \Gdpr::PERM_DELETE_USERS;
            return $list;
        });

        \Eventy::addFilter('user_permissions.name', function($name, $permission) {
            if ($permission != \Gdpr::PERM_DELETE_USERS) {
                return $name;
            }
            return __('Users are allowed to delete customers with their conversations');
        }, 20, 2);

        \Eventy::addAction('customer.edit.before_form', function($customer) {
            if ($customer->getMeta('gdpr_deleting')) {
                ?>
                    <div class="alert alert-warning">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <?php echo __('Customer currently is being deleted in the background...'); ?>
                    </div>
                <?php
            }
        });
    }

    public static function canDeleteUsers($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if (!$user) {
            return false;
        }
        return $user->isAdmin() || $user->hasPermission(\Gdpr::PERM_DELETE_USERS);
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
            __DIR__.'/../Config/config.php' => config_path('gdpr.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'gdpr'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/gdpr');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/gdpr';
        }, \Config::get('view.paths')), [$sourcePath]), 'gdpr');
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
