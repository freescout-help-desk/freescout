<?php

namespace Modules\ExportConversations\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('EC_MODULE', 'exportconversations');

class ExportConversationsServiceProvider extends ServiceProvider
{
    const CUSTOM_FIELD_PREFIX = 'ccf_';
    const MODULE_PREFIX = 'module_';
    const BUNCH_SIZE = 1000;

    public static $exportable_fields = [];

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
            $javascripts[] = \Module::getPublicPath(EC_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(EC_MODULE).'/js/module.js';
            return $javascripts;
        });

        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(EC_MODULE).'/css/module.css';
            return $styles;
        });

        \Eventy::addAction('search.conversations_tab_append', function($filters, $count) {
            if (request()->mode != "customers" && $count) {
                $f = request()->f;
                $f['q'] = request()->q ?? '';
                // if (!empty(request()->f)) {
                //     foreach (request()->f as $key => $value) {
                //         if (is_array($value)) {
                //             $f[$key] = $value[0];
                //         } else {
                //             $f[$key] = $value;
                //         }
                //     }
                // }
                ?>
                    <i class="glyphicon glyphicon-download-alt ec-search-export fs-trigger-modal" data-toggle="tooltip" title="<?php echo __('Export Conversations') ?>" data-remote="<?php echo route('exportconversations.ajax_html', ['action' => 'export', 'f' => $f]) ?>" data-modal-title="<?php echo __('Export Conversations') ?> (PDF)" data-modal-no-footer="true"></i>
                <?php
            }
        }, 20, 2);

        \Eventy::addAction('reports.filters_button_append', function() {
            ?>
                <div class="rpt-filter" data-toggle="tooltip" title="<?php echo __('Export Conversations') ?>">
                    <button class="btn btn-primary fs-trigger-modal" id="rpt-btn-export" data-remote="<?php echo route('exportconversations.ajax_html', ['action' => 'export']) ?>" data-modal-title="<?php echo __('Export Conversations') ?> (PDF)" data-modal-no-footer="true"><i class="glyphicon glyphicon-download-alt"></i></button>
                </div>
            <?php
        });
    }

    public static function getExportableFields($mailbox_ids = [])
    {
        if (!empty(self::$exportable_fields)) {
            return self::$exportable_fields;
        }
        self::$exportable_fields = [
            'id' => 'ID',
            'number' => 'Conversation Number',
            'type' => 'Type',
            'user_id' => 'Assignee',
            'status' => 'Status',
            'state' => 'State',
            'mailbox_id' => 'Mailbox',
            'customer_name' => 'Customer Name',
            'customer_email' => 'Customer Email',
            'threads_count' => 'Threads Count',
            'subject' => 'Subject',
            'cc' => 'CC',
            'bcc' => 'BCC',
            'has_attachments' => 'Has Attachments',
            'channel' => 'Channel',
            'created_at' => 'Created On',
            'last_reply_at' => 'Last Reply On',
            'last_reply_from' => 'Last Reply From',
            'closed_at' => 'Closed On',
            'closed_by_user_id' => 'Closed By',
        ];

        if (\Module::isActive('tags')) {
            self::$exportable_fields[self::MODULE_PREFIX.'tags'] = __('Tags');
        }
        if (\Module::isActive('timetracking')) {
            self::$exportable_fields[self::MODULE_PREFIX.'time_spent'] = __('Time Spent');
        }
        if (\Module::isActive('satratings')) {
            self::$exportable_fields[self::MODULE_PREFIX.'sat_ratings'] = __('Sat. Ratings');
        }
        if (\Module::isActive('customfields')) {
            if (empty($mailbox_ids)) {
                $mailbox_ids = auth()->user()->mailboxesIdsCanView();
            }
            $custom_fields = \CustomField::whereIn('mailbox_id', $mailbox_ids)
                ->orderBy('sort_order')
                ->get();
            foreach ($custom_fields as $custom_field) {
                self::$exportable_fields[self::MODULE_PREFIX.'ccf_'.$custom_field->id] = $custom_field->name;
            }
        }

        if (\Module::isActive('crm')) {
            $customer_fields = \CustomerField::getCustomerFields();
            foreach ($customer_fields as $customer_field) {
                self::$exportable_fields[self::MODULE_PREFIX.'crm_'.$customer_field->id] = '('.__('Customer').') '.$customer_field->name;
            }
        }

        self::$exportable_fields = \Eventy::filter('exportconversations.exportable_fields', self::$exportable_fields);

        return self::$exportable_fields;
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
            __DIR__.'/../Config/config.php' => config_path('exportconversations.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'exportconversations'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/exportconversations');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/exportconversations';
        }, \Config::get('view.paths')), [$sourcePath]), 'exportconversations');
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
