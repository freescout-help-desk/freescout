<?php

namespace Modules\Crm\Providers;

use App\Conversation;
use Modules\Crm\Entities\CustomerField;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias.
define('CRM_MODULE', 'crm');

class CrmServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public static $search_customer_fields = [];

    public static $exportable_fields = [];

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
        // \Eventy::addFilter('stylesheets', function($styles) {
        //     $styles[] = \Module::getPublicPath(CRM_MODULE).'/css/module.css';
        //     return $styles;
        // });
        
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(CRM_MODULE).'/js/laroute.js';
            if (!preg_grep("/html5sortable\.js$/", $javascripts)) {
                $javascripts[] = \Module::getPublicPath(CRM_MODULE).'/js/html5sortable.js';
            }
            $javascripts[] = \Module::getPublicPath(CRM_MODULE).'/js/module.js';
            return $javascripts;
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            $customer_fields = \CustomerField::getCustomerFields();
            if (count($customer_fields)) {
                $customer_vars = [];
                foreach ($customer_fields as $customer_field) {
                    $customer_vars['customer.'.$customer_field->getNameEncoded()] = $customer_field->name.' ('.$customer_field->getNameEncoded().')';
                }
                
                echo 'crmInitVars('.json_encode($customer_vars).');';
            }
        });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['customer-fields'] = ['title' => __('Customer Fields'), 'icon' => 'list-alt', 'order' => 250];

            return $sections;
        }, 16);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != 'customer-fields') {
                return $settings;
            }
           
            $settings['customer_fields'] = \CustomerField::getCustomerFields();
            $settings['crm.conv_fields'] = json_decode(config('crm.conv_fields'), true) ?? [];

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != 'customer-fields') {
                return $params;
            }

            $params = [
                'settings' => [
                    'crm.conv_fields' => [
                        'env' => 'CRM_CONV_FIELDS',
                    ],
                ],
            ];

            return $params;
        }, 20, 2);

        // Settings view name.
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != 'customer-fields') {
                return $view;
            } else {
                return 'crm::customer_fields';
            }
        }, 20, 2);

        // JS messages.
        \Eventy::addAction('js.lang.messages', function() {
            ?>
                "crm_confirm_delete_customer_field": "<?php echo __("Deleting this Customer Field will remove all historical data. Delete this custom field?") ?>",
                "crm_confirm_delete_option": "<?php echo __("Deleting this dropdown option will remove all historical data. Delete this dropdown option?") ?>",
            <?php
        });

        // Add item to the mailbox menu
        \Eventy::addAction('menu.manage.after_mailboxes', function($mailbox) {
            echo \View::make('crm::partials/menu', [])->render();
        });

        \Eventy::addAction('javascript', function($menu) {
            if (self::isCrm()) {
                echo 'initCrm();';
            }
            // Refresh customers after saving a customer in modal.
            if (\Route::currentRouteName() == 'customers.update' && !empty(session('customer.updated'))) {
                echo 'crmTriggerRefresh();';
            }
        });

        \Eventy::addAction('customer.card.link', function($customer) {
            if (self::isCrm() || request()->action == 'customers_pagination') {
                ?> data-trigger="modal" data-modal-title="<?php echo htmlspecialchars(htmlspecialchars($customer->getFullName(true))) ?>" data-modal-size="lg" data-modal-no-footer="true" data-modal-body='<iframe src="<?php echo route('customers.update', ['id' => $customer->id, 'x_embed' => 1]) ?>" frameborder="0" class="modal-iframe"></iframe>'
                <?php
            }
        }, 20, 1);

        // \Eventy::addFilter('customer.card.url', function($url, $customer) {
        //     if (self::isCrm()) {
        //         return route('customers.update', ['id' => $customer->id, 'x_embed' => 1]);
        //     }

        //     return $url;
        // }, 20, 2);
        
        // Select main menu item.
        \Eventy::addFilter('menu.selected', function($menu) {
            if (self::isCrm()) {
                $menu['manage']['crm'] = [
                    'conversations.search'
                ];
            }

            return $menu;
        });

        \Eventy::addFilter('search.title', function($title) {
            if (self::isCrm()) {
                $html = __('Customers').' <a href="#" data-trigger="modal" data-modal-title="'.__('Add Customer').'" data-modal-size="lg" data-modal-no-footer="true" data-modal-body=\'<iframe src="'.route('crm.create_customer', ['x_embed' => 1]).'" frameborder="0" class="modal-iframe"></iframe>\' class="btn btn-bordered btn-xs" style="position:relative;top:-1px;margin-left:4px;"><i class="glyphicon glyphicon-plus" title="'.__('Add Customer').'" data-toggle="tooltip"></i></a>';

                if (\Auth::user()->isAdmin()) {
                    $html .= '<span class="dropdown">
                        <a href="javascript:void(0)" class="dropdown-toggle btn btn-xs" data-toggle="dropdown"><span class="caret"></span></a>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li>
                                <a href="'.route('crm.ajax_html', ['action' => 'delete_without_conv']).'" data-trigger="modal" data-modal-title="'.__('Delete Customers Without Conversations').'" data-modal-no-footer="true" data-modal-on-show="crmInitDeleteWithoutConv" style="position:relative;top:-1px;margin-left:9px;" title="'.__('Delete Customers Without Conversations').'">'.__('Clean Customers').'</a>
                            </li>
                            <li>
                                <a href="'.route('crm.ajax_html', ['action' => 'import']).'" data-trigger="modal" data-modal-title="'.__('Import Customers').'" data-modal-no-footer="true" data-modal-on-show="crmImportModal" style="position:relative;top:-1px;margin-left:9px;">'.__('Import Customers').'</a>
                            </li>
                            <li>
                                <a href="'.route('crm.ajax_html', ['action' => 'export']).'" data-trigger="modal" data-modal-title="'.__('Export Customers').' (CSV)" data-modal-no-footer="true" style="position:relative;top:-1px;margin-left:9px;">'.__('Export Customers').'</i></a>
                            </li>
                        </ul>
                    </span>';

                    $html .= '';
                }

                return $html;
            }

            return $title;
        }, 20, 1);

        \Eventy::addFilter('search.is_tab_visible', function($is_visible, $mode) {
            if (self::isCrm()) {
                return false;
            }

            return $is_visible;
        }, 20, 2);

        \Eventy::addFilter('search.is_needed', function($is_needed, $entity) {
            if (self::isCrm() && $entity == 'conversations') {
                return false;
            }

            return $is_needed;
        }, 20, 2);

        // Add hidden inuput with xs_crm
        \Eventy::addAction('search.display_filters', function($filters, $filters_data, $mode) {
            if (!self::isCrm()) {
                return false;
            }

            echo '<input type="hidden" name="xs_crm" value="1" />';
        }, 20, 3);

        // Show block in conversation
        \Eventy::addAction('customer.edit.after_fields', function($customer, $errors) {

            $customer_fields = CustomerField::getCustomerFieldsWithValues($customer->id);

            if (!$customer_fields) {
                return;
            }

            echo \View::make('crm::partials/customer_fields_edit', ['customer_fields' => $customer_fields])->render();
        }, 20, 2);

        \Eventy::addAction('customer.set_data', function($customer, $data, $replace_data) {

            $customer_fields = CustomerField::getCustomerFields();

            if (!$customer_fields) {
                return;
            }

            foreach ($customer_fields as $customer_field) {
                foreach ($data as $data_field => $data_value) {
                    if ($data_field == $customer_field->getNameEncoded()) {
                        if (!$customer->id) {
                            $customer->save();
                        }
                        CustomerField::setValue($customer->id, $customer_field->id, $data_value);
                        break;
                    }
                }
            }
        }, 20, 3);

        \Eventy::addAction('customer.profile.extra', function($customer) {

            $customer_fields = CustomerField::getCustomerFieldsWithValues($customer->id);

            if (!$customer_fields) {
                return;
            }

            echo \View::make('crm::partials/customer_fields_view', ['customer_fields' => $customer_fields])->render();
        });

        // Search filters.
        \Eventy::addFilter('search.filters_list_customers', function($filters_list) {
            $customer_fields = $this->getSearchCustomFields();

            if (count($customer_fields)) {
                $customer_fields = $customer_fields->pluck('name')->toArray();

                if (count($customer_fields)) {
                    $filters_list = array_merge($filters_list, $customer_fields);
                }
            }

            return $filters_list;
        });

        // Display search filters.
        \Eventy::addAction('search.display_filters', function($filters) {
            $customer_fields = $this->getSearchCustomFields();

            if (count($customer_fields)) {
                echo \View::make('crm::partials/cf_search_filters', [
                    'customer_fields' => $customer_fields,
                    'filters'       => $filters,
                ])->render();
            }
        });

        // Search filters apply.
        \Eventy::addFilter('search.customers.apply_filters', function($query_customers, $filters, $q) {
            $customer_fields = $this->getSearchCustomFields();

            if (count($customer_fields)) {
                foreach ($customer_fields as $customer_field) {
                    if (!empty($filters[$customer_field->name])) {
                        $join_alias = 'ccf'.$customer_field->id;
                        $query_customers->join('customer_customer_field as '.$join_alias, function ($join) use ($customer_field, $filters, $join_alias) {
                            $join->on('customers.id', '=', $join_alias.'.customer_id');
                            $join->where($join_alias.'.customer_field_id', $customer_field->id);
                            if ($customer_field->type == CustomerField::TYPE_MULTI_LINE) {
                                $join->where($join_alias.'.value', 'like', '%'.$filters[$customer_field->name].'%');
                            } else {
                                $join->where($join_alias.'.value', $filters[$customer_field->name]);
                            }
                        });
                    }
                }
            }

            return $query_customers;
        }, 20, 3);

        // Display search filters.
        \Eventy::addFilter('customer.profile_menu', function($html, $customer) {
            $html .= \View::make('crm::partials/profile_menu', [
                'customer' => $customer,
            ])->render();

            return $html;
        }, 100, 2);

        // Show customer data in conversation list.
        \Eventy::addAction('conversations_table.before_subject', function($conversation) {
            if (!$conversation->customer_id || !$conversation->customer) {
                return;
            }
            // Standard fields.
            $conv_fields = json_decode(config('crm.conv_fields'), true) ?? [];
            if ($conv_fields) {
                $customer = $conversation->customer;
                foreach ($conv_fields as $conv_field) {
                    $field_text = '';
                    switch ($conv_field) {
                        case 'email':
                            $field_text = $customer->getMainEmail();
                            break;
                        case 'phone':
                            $field_text = $customer->getMainPhoneNumber();
                            break;
                        case 'website':
                            $field_text = $customer->getMainWebsite();
                            break;
                        default:
                            $field_text = $customer->$conv_field;
                            break;
                    }
                    if ($field_text) {
                        $field_name = __(ucwords($conv_field));
                        echo \View::make('crm::partials/conv_list_field', [
                            'field_name' => $field_name,
                            'field_text' => $field_text,
                        ])->render();
                    }
                }
            }

            // Custom customer fields.
            if (!empty($conversation->customer_fields)) {
                foreach ($conversation->customer_fields as $customer_field) {
                    $field_text = $customer_field->getAsText();
                    if ($field_text) {
                        echo \View::make('crm::partials/conv_list_field', [
                            'field_name' => $customer_field->name,
                            'field_text' => $field_text,
                        ])->render();
                    }
                }
            }
        });

        // Preload customer fields in conversation list.
        \Eventy::addFilter('conversations_table.preload_table_data', function($conversations) {
            $has_conv_list_fields = false;

            $customer_fields = CustomerField::getCustomerFields();
            foreach ($customer_fields as $customer_field) {
                if ($customer_field->conv_list) {
                    $has_conv_list_fields = true;
                    break;
                }
            }
            if (!$has_conv_list_fields) {
                return $conversations;
            }

            $customer_ids = $conversations->pluck('customer_id')->unique()->toArray();

            $customer_fields = CustomerField::select(['customer_fields.*', 'customer_customer_field.value', 'customer_customer_field.customer_id'])
                ->join('customer_customer_field', function ($join) {
                    $join->on('customer_customer_field.customer_field_id', 'customer_fields.id');
                })
                ->where('customer_fields.conv_list', true)
                ->whereIn('customer_customer_field.customer_id', $customer_ids)
                ->get();

            if (!count($customer_fields)) {
                return $conversations;
            }

            foreach ($conversations as $i => $conversation) {
                if (!$conversation->customer_id) {
                    continue;
                }
                // Find conversation customer fields.
                foreach ($customer_fields as $customer_field) {
                    if ($conversation->customer_id == $customer_field->customer_id) {
                        $new_customer_fields = $conversation->customer_fields ?? [];
                        $new_customer_fields[] = $customer_field;
                        $conversation->customer_fields = $new_customer_fields;
                    }
                }
            }

            return $conversations;
        });

        \Eventy::addFilter('mail_vars.replace', function($vars, $data) {
            if (empty($data['customer'])) {
                return $vars;
            }
            $customer_fields = CustomerField::getCustomerFieldsWithValues($data['customer']->id);

            if (!$customer_fields) {
                return $vars;
            }

            foreach ($customer_fields as $customer_field) {
                $vars['{%customer.'.$customer_field->getNameEncoded().'%}'] = $customer_field->value;
            }

            return $vars;
        }, 20, 2);
    }

    public function isCrm()
    {
        return !empty(request()->xs_crm);
        //return \Helper::isRoute('conversations.search') && request()->mode == Conversation::SEARCH_MODE_CUSTOMERS;
    }

    public function getSearchCustomFields()
    {
        if (self::$search_customer_fields) {
            return self::$search_customer_fields;
        }

        $customer_fields = CustomerField::getCustomerFields();

        if (count($customer_fields)) {
            foreach ($customer_fields as $i => $customer_field) {
                $customer_fields[$i]->name = '#'.$customer_field->name;
            }
            self::$search_customer_fields = $customer_fields;
            return $customer_fields;
        }

        return [];
    }

    public static function getExportableFields()
    {
        if (!empty(self::$exportable_fields)) {
            return self::$exportable_fields;
        }
        self::$exportable_fields = [
            'customers.id' => 'ID',
            'first_name' => __('First Name'),
            'last_name' => __('Last Name'),
            'emails' => __('Email'),
            'phones' => __('Phone'),
            'company' => __('Company'),
            'job_title' => __('Job Title'),
            'websites' => __('Website'),
            'social_profiles' => __('Social Profiles'),
            'country' => __('Country'),
            'state' => __('State'),
            'city' => __('City'),
            'zip' => __('ZIP'),
            'address' => __('Address'),
            'photo_url' => __('Photo'),
            'notes' => __('Notes'),
        ];

        $customer_fields = CustomerField::getCustomerFields();

        foreach ($customer_fields as $customer_field) {
            self::$exportable_fields[$customer_field->getNameEncoded()] = $customer_field->name;
        }

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
            __DIR__.'/../Config/config.php' => config_path('crm.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'crm'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/crm');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/crm';
        }, \Config::get('view.paths')), [$sourcePath]), 'crm');
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
