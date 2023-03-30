<?php

namespace Modules\Reports\Providers;

use App\Conversation;
use App\Thread;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('REPORTS_MODULE', 'reports');

class ReportsServiceProvider extends ServiceProvider
{
    const REPORT_CONVERSATIONS = 'conversations';
    const REPORT_PRODUCTIVITY  = 'productivity';
    const REPORT_SATISFACTION  = 'satisfaction';
    const REPORT_TIME          = 'time';

    const MAX_TABLE_ITEMS = 20;

    // User permission.
    const PERM_ACCESS_REPORTS = 8;

    const DATA_COLLECT_BUNCH = 200;

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
        $this->registerCommands();
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
            $styles[] = \Module::getPublicPath(REPORTS_MODULE).'/css/module.css';
            return $styles;
        });
        
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(REPORTS_MODULE).'/js/laroute.js';
            //$javascripts[] = \Module::getPublicPath(REPORTS_MODULE).'/js/highcharts.js';
            $javascripts[] = \Module::getPublicPath(REPORTS_MODULE).'/js/module.js';
            return $javascripts;
        });

        // Add item to the mailbox menu
        \Eventy::addAction('menu.append', function($mailbox) {
            if (self::canAccessReports()) {
                echo \View::make('reports::partials/menu', [])->render();
            }
        });

        \Eventy::addFilter('menu.selected', function($menu) {
            if (auth()->user() && auth()->user()->isAdmin()) {
                $menu['reports'] = [
                    'reports.conversations',
                    'reports.productivity',
                    'reports.satisfaction',
                    'reports.time',
                ];
            }
            return $menu;
        });

        \Eventy::addFilter('user_permissions.list', function($list) {
            $list[] = self::PERM_ACCESS_REPORTS;
            return $list;
        });

        \Eventy::addFilter('user_permissions.name', function($name, $permission) {
            if ($permission != self::PERM_ACCESS_REPORTS) {
                return $name;
            }
            return __('Users are allowed to access reports');
        }, 20, 2);

        // Prepare reports data in background.
        \Eventy::addFilter('schedule', function($schedule) {
            $schedule->command('freescout:reports-collect-data')->cron('* * * * *');

            return $schedule;
        });

        \Eventy::addAction('thread.created', function($thread) {
            if (!in_array($thread->type, [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])) {
                return;
            }
            $thread->conversation->rpt_ready = false;
            $thread->conversation->save();
        });

        \Eventy::addAction('conversation.status_changed', function($conversation, $user, $changed_on_reply, $prev_status) {
            if ($conversation->status == Conversation::STATUS_CLOSED && $prev_status != Conversation::STATUS_CLOSED
                || $conversation->status != Conversation::STATUS_CLOSED && $prev_status == Conversation::STATUS_CLOSED
            ) {
                $conversation->rpt_ready = false;
                $conversation->save();
            }
        }, 20, 4);
    }

    public static function canAccessReports($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if (!$user) {
            return false;
        }
        return $user->isAdmin() || $user->hasPermission(self::PERM_ACCESS_REPORTS);
    }

    public static function getCustomFieldFilters()
    {
        $custom_fields = [];

        if (\Module::isActive('customfields')) {

            $mailbox_ids = auth()->user()->mailboxesIdsCanView();

            if ($mailbox_ids) {
                $custom_fields = \Modules\CustomFields\Entities\CustomField::whereIn('mailbox_id', $mailbox_ids)
                    ->get();
            }
        }
        return $custom_fields;
    }

    /**
     * Collect data for response time reports.
     */
    public static function collectData()
    {
        $conversations = [];

        $robots_ids = null;

        do {
            $conversations = Conversation::where('rpt_ready', false)
                ->limit(self::DATA_COLLECT_BUNCH)
                ->orderBy('id')
                ->get();

            if (count($conversations) && $robots_ids === null) {
                $robots_ids = User::getRobotsCondition()->pluck('id')->toArray();
            }

            foreach ($conversations as $conversation) {
                //echo $conversation->id.' ';

                // Skip spam messages.
                if ($conversation->isSpam() || $conversation->threads_count <= 1) {
                    $conversation->rpt_ready = true;
                    $conversation->save();
                    continue;
                }

                $meta = $conversation->getMeta('rpt') ?: [];
                // First response time.
                $meta['frt'] = 0;
                // Response time.
                $meta['rst'] = 0;
                // Resolution time.
                $meta['rnt'] = 0;
                // Replies to resolve.
                $meta['rtr'] = 0;
                // Resolved on first reply.
                $meta['rfr'] = false;

                $threads = $conversation->threads()
                    ->whereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])
                    ->where('state', Thread::STATE_PUBLISHED)
                    ->orderBy('created_at', 'asc')
                    ->get();

                $response_times = [];
                $last_customer_reply = null;
                foreach ($threads as $i => $thread) {
                    if ($i > 0 && $thread->isUserMessage() 
                        && !in_array($thread->created_by_user_id, $robots_ids)
                    ) {
                        // First response time.
                        if (!$meta['frt']) {
                            $meta['frt'] = $thread->created_at->timestamp - $conversation->created_at->timestamp;

                            // Resolved on first reply (1/2).
                            // if ($conversation->isClosed() && $thread->status == Thread::STATUS_CLOSED) {
                            //     $meta['ror'] = true;
                            // }
                        }
                        // Response time.
                        if ($last_customer_reply) {
                            $response_times[] = $thread->created_at->timestamp - $last_customer_reply->created_at->timestamp;
                        }
                        // Replies to resolve.
                        if ($conversation->isClosed() && $conversation->closed_at 
                            // Add 10 seconds in case there was a delay between setting of closed_at and thread creation.
                            && ($thread->created_at->timestamp-10 <= $conversation->closed_at->timestamp)
                        ) {
                            $meta['rtr']++;
                        }
                    }

                    if ($thread->isCustomerMessage()) {
                        $last_customer_reply = $thread;
                    }
                }

                // Resolution time.
                if ($conversation->isClosed() && $conversation->closed_at) {
                    $meta['rnt'] = $conversation->closed_at->timestamp - $conversation->created_at->timestamp;
                }
                // Response time.
                // Here we may need to store response times as an array.
                $meta['rst'] = self::getMedianValue($response_times);
                if (!$meta['rfr'] && $meta['rtr'] == 1) {
                    $meta['rfr'] = true;
                }

                $conversation->setMeta('rpt', $meta);

                // Set rpt_ready.
                $conversation->rpt_ready = true;
                $conversation->save();
                // echo "<pre>";
                // print_r($meta);
                // exit();
            }
        } while(count($conversations));
    }

    public static function getMedianValue($values)
    {
        $count = count($values);

        if ($count == 0) {
            return 0;
        }
        asort($values);
        
        $half = floor($count / 2);

        if ($count % 2) {
            return $values[$half];
        } else {
            return floor(($values[$half - 1] + $values[$half]) / 2.0);
        }
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
            __DIR__.'/../Config/config.php' => config_path('reports.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'reports'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/reports');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/reports';
        }, \Config::get('view.paths')), [$sourcePath]), 'reports');
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
     * https://github.com/nWidart/laravel-modules/issues/626
     * https://github.com/nWidart/laravel-modules/issues/418#issuecomment-342887911
     * @return [type] [description]
     */
    public function registerCommands()
    {
        $this->commands([
            \Modules\Reports\Console\CollectData::class
        ]);
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
