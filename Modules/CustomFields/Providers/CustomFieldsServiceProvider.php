<?php

namespace Modules\CustomFields\Providers;

use App\Conversation;
use Carbon\Carbon;
use Modules\CustomFields\Entities\CustomField;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('CF_MODULE', 'customfields');

class CustomFieldsServiceProvider extends ServiceProvider
{
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

    public function hooks()
    {
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(CF_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(CF_MODULE).'/js/laroute.js';
            if (!preg_grep("/html5sortable\.js$/", $javascripts)) {
                $javascripts[] = \Module::getPublicPath(CF_MODULE).'/js/html5sortable.js';
            }
            $javascripts[] = \Module::getPublicPath(CF_MODULE).'/js/module.js';

            return $javascripts;
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view') || \Route::is('conversations.create')) {
                echo 'initCustomFields();';
            }
        });

        // JS messages
        \Eventy::addAction('js.lang.messages', function() {
            ?>
                "confirm_delete_custom_field": "<?php echo __("Deleting this custom field will remove all historical data and deactivate related workflows. Delete this custom field?") ?>",
                "confirm_delete_cf_option": "<?php echo __("Deleting this dropdown option will remove all historical data and deactivate related workflows. Delete this dropdown option?") ?>",
            <?php
        });

        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('customfields::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 15);

        // Show block in conversation
        \Eventy::addAction('conversation.after_subject_block', function($conversation, $mailbox) {

            //$custom_fields = CustomField::getMailboxCustomFields($mailbox->id);
            $custom_fields = CustomField::getCustomFieldsWithValues($mailbox->id, $conversation->id);

            if (!$custom_fields) {
                return;
            }

            echo \View::make('customfields::partials/fields_view', ['custom_fields' => $custom_fields])->render();
        }, 30, 2);

        // Show on conversation creation
        \Eventy::addAction('conversation.create_form.after_subject', function($conversation, $mailbox) {
            
            $custom_fields = CustomField::getCustomFieldsWithValues($mailbox->id, $conversation->id);

            if (!$custom_fields) {
                return;
            }

            echo \View::make('customfields::partials/fields_view', [
                'custom_fields' => $custom_fields,
                'on_create'     => true,
            ])->render();
        }, 20, 2);

        // Search filters.
        \Eventy::addFilter('search.filters_list', function($filters_list) {
            $custom_fields = CustomField::getSearchCustomFields();

            if (count($custom_fields)) {
                $custom_fields = $custom_fields->pluck('name')->toArray();

                if (count($custom_fields)) {
                    $filters_list = array_merge($filters_list, $custom_fields);
                }
            }

            return $filters_list;
        });

        // Display search filters.
        \Eventy::addAction('search.display_filters', function($filters) {
            $custom_fields = CustomField::getSearchCustomFields();

            if (count($custom_fields)) {
                echo \View::make('customfields::partials/search_filters', [
                    'custom_fields' => $custom_fields,
                    'filters'       => $filters,
                ])->render();
            }
        });

        // Search filters apply.
        \Eventy::addFilter('search.conversations.apply_filters', function($query_conversations, $filters, $q) {
            $custom_fields = CustomField::getSearchCustomFields();

            if (count($custom_fields)) {
                foreach ($custom_fields as $custom_field) {
                    if (!empty($filters[$custom_field->name])) {
                        $join_alias = 'ccf'.$custom_field->id;
                        $query_conversations->join('conversation_custom_field as '.$join_alias, function ($join) use ($custom_field, $filters, $join_alias) {
                            $join->on('conversations.id', '=', $join_alias.'.conversation_id');
                            $join->where($join_alias.'.custom_field_id', $custom_field->id);
                            if ($custom_field->type == CustomField::TYPE_MULTI_LINE) {
                                $join->where($join_alias.'.value', 'like', '%'.$filters[$custom_field->name].'%');
                            } else {
                                $join->where($join_alias.'.value', $filters[$custom_field->name]);
                            }
                        });
                    }
                }
            }

            return $query_conversations;
        }, 20, 3);

        // Workflows.
        
        \Eventy::addFilter('workflows.conditions_config', function($conditions, $mailbox_id = null) {
            
            if (!$mailbox_id) {
                return $conditions;
            }

            $fields = CustomField::getMailboxCustomFields($mailbox_id);

            if (count($fields)) {
                $conditions['custom_fields'] = [
                    'title' => __('Custom Fields'),
                    'items' => []
                ];

                foreach ($fields as $field) {
                    $config = [];

                    switch ($field->type) {
                        case CustomField::TYPE_DROPDOWN:
                            $config = [
                                'title' => $field->name,
                                'operators' => [
                                    'equal' => __('Is equal to'),
                                    'not_equal' => __('Is not equal to'),
                                    'not_empty' => __('Is set'),
                                    'empty' => __('Is not set'),
                                ],
                                'values' => $field->options,
                                'triggers' => [
                                    'custom_field.value_updated'
                                ]
                            ];
                            break;
                        
                        case CustomField::TYPE_SINGLE_LINE:
                            $config = [
                                'title' => $field->name,
                                'operators' => [
                                    'equal' => __('Is equal to'),
                                    'contains' => __('Contains'),
                                    'not_contains' => __('Does not contain'),
                                    'not_equal' => __('Is not equal to'),
                                    'starts' => __('Starts with'),
                                    'ends' => __('Ends with'),
                                    'regex' => __('Matches regex pattern'),
                                    'not_empty' => __('Is set'),
                                    'empty' => __('Is not set'),
                                ],
                                'triggers' => [
                                    'custom_field.value_updated'
                                ]
                            ];
                            break;

                        case CustomField::TYPE_MULTISELECT:
                            $config = [
                                'title' => $field->name,
                                'operators' => [
                                    'contains' => __('Contains'),
                                    'not_contains' => __('Does not contain'),
                                    'regex' => __('Matches regex pattern'),
                                    'not_empty' => __('Is set'),
                                    'empty' => __('Is not set'),
                                ],
                                'triggers' => [
                                    'custom_field.value_updated'
                                ]
                            ];
                            break;

                        case CustomField::TYPE_MULTI_LINE:
                            $config = [
                                'title' => $field->name,
                                'operators' => [
                                    'contains' => __('Contains'),
                                    'not_contains' => __('Does not contain'),
                                    'equal' => __('Is equal to'),
                                    'not_equal' => __('Is not equal to'),
                                    'not_empty' => __('Is set'),
                                    'empty' => __('Is not set'),
                                ],
                                'triggers' => [
                                    'custom_field.value_updated'
                                ]
                            ];
                            break;

                        case CustomField::TYPE_NUMBER:
                            $config = [
                                'title' => $field->name,
                                'operators' => [
                                    'equal' => __('Is equal to'),
                                    'not_equal' => __('Is not equal to'),
                                    'greater' => __('Is greater than'),
                                    'less' => __('Is less than'),
                                    'not_empty' => __('Is set'),
                                    'empty' => __('Is not set'),
                                ],
                                'values_type' => 'number',
                                'triggers' => [
                                    'custom_field.value_updated'
                                ]
                            ];
                            break;

                        case CustomField::TYPE_DATE:
                            $config = [
                                'title' => $field->name,
                                'operators' => [
                                    'past' => __('Is in the past'),
                                    'future' => __('Is in the future'),
                                    'today' => __('Is today'),
                                    'next_days' => __('Is in the next (days)'),
                                    'not_next_days' => __('Is not in the next (days)'),
                                    'last_days' => __('Was in the last (days)'),
                                    'not_last_days' => __('Was not in the last (days)'),
                                    'not_empty' => __('Is set'),
                                    'empty' => __('Is not set'),
                                ],
                                'triggers' => [
                                    'custom_field.value_updated'
                                ],
                                'values_visible_if' => [
                                    'next_days', 
                                    'last_days',
                                    'not_next_days', 
                                    'not_last_days', 
                                ]
                            ];
                            break;
                    }

                    if ($config) {
                        $conditions['custom_fields']['items']['cf_'.$field->id] = $config;
                    }
                }
            }

            return $conditions;
        }, 20, 2);

        \Eventy::addAction('custom_field.value_updated', function($field, $conversation_id) {
            if (!\Module::isActive('workflows')) {
                return;
            }
            $custom_field = CustomField::find($field->custom_field_id);
            if ($custom_field) {
                $conversation = Conversation::find($conversation_id);
                if ($conversation) {
                    \Workflow::runAutomaticForConversation($conversation, 'custom_field.value_updated');
                }
            }
        }, 20, 2);

        \Eventy::addFilter('workflow.check_condition', function($result, $type, $operator, $value, $conversation, $workflow) {
            preg_match("/cf_(\d+)/", $type, $m);
            if (empty($m[1])) {
                return $result;
            }
            $custom_field_id = $m[1];
            $custom_field = CustomField::find($custom_field_id);
            if (!$custom_field) {
                return false;
            }
            $custom_field_value = CustomField::getValue($conversation->id, $custom_field_id);

            switch ($custom_field->type) {
                case CustomField::TYPE_DROPDOWN:
                case CustomField::TYPE_SINGLE_LINE:
                case CustomField::TYPE_MULTI_LINE:
                case CustomField::TYPE_MULTISELECT:
                    return \Workflow::compareText($custom_field_value, $value, $operator);
                    break;
                
                case CustomField::TYPE_NUMBER:
                    if ($operator == 'greater') {
                        return is_numeric($value) && (int)$custom_field_value > (int)$value;
                    } elseif ($operator == 'less') {
                        return is_numeric($value) && (int)$custom_field_value < (int)$value;
                    } else {
                        return \Workflow::compareText($custom_field_value, $value, $operator);
                    }                   
                    break;

                case CustomField::TYPE_DATE:
                    if ($custom_field_value) {
                        $cf_date = null;
                        try {
                            $cf_date = Carbon::parse($custom_field_value);
                        } catch (\Exception $e) {
                            // Do nothing.
                        }

                        if ($cf_date) {
                            $now = Carbon::now();
                            if ($operator == 'past') {
                                return $cf_date < $now;
                            } elseif ($operator == 'future') {
                                return $cf_date > $now;
                            } elseif ($operator == 'today') {
                                return $cf_date->toDateString() == $now->toDateString();
                            } elseif ($operator == 'next_days') {
                                return $cf_date > $now && $cf_date < $now->addDays((int)$value+1);
                            } elseif ($operator == 'last_days') {
                                return $cf_date < $now && $cf_date > $now->subDays((int)$value+1);
                            }  elseif ($operator == 'not_next_days') {
                                return $cf_date < $now || $cf_date > $now->addDays((int)$value+1);
                            } elseif ($operator == 'not_last_days') {
                                return $cf_date > $now || $cf_date < $now->subDays((int)$value+1);
                            } elseif ($operator == 'not_empty') {
                                return true;
                            }
                        }
                    }
                    return \Workflow::compareText($custom_field_value, $value, $operator);
                    break;
            }
            return false;
        }, 20, 6);

        \Eventy::addFilter('workflows.actions_config', function($actions, $mailbox_id = null) {
            $custom_fields = CustomField::getMailboxCustomFields($mailbox_id, true);

            $operators = [];
            foreach ($custom_fields as $custom_field) {
                $operators[$custom_field->id] = $custom_field->name;
            }

            $actions['dummy']['items']['set_custom_field'] = [
                'title' => __('Set Custom Field'),
                'operators' => $operators,
                'values_custom' => true
            ];
            return $actions;
        }, 20, 2);

        \Eventy::addAction('workflows.values_custom', function($type, $value, $mode, $and_i, $row_i, $data) {
            if ($type != 'set_custom_field') {
                return;
            }
            $custom_fields = CustomField::getMailboxCustomFields($data['mailbox']->id, true);

            foreach ($custom_fields as $custom_field) {
                switch ($custom_field->type) {

                    case CustomField::TYPE_DROPDOWN:
                        ?>
                            <select class="form-control wf-multi-value wf-multi-value-<?php echo $custom_field->id ?>" name="<?php echo $mode ?>[<?php echo $and_i ?>][<?php echo $row_i ?>][value]" disabled>
                                <?php foreach ($custom_field->options as $option_key => $option_value): ?>
                                    <option value="<?php echo $option_key ?>" <?php if ($value == $option_key): ?> selected <?php endif ?>><?php echo $option_value ?></option>
                                <?php endforeach ?>
                            </select>
                        <?php
                        break;

                    default:
                        ?>
                            <input type="<?php if ($custom_field->type == CustomField::TYPE_NUMBER): ?>number<?php else: ?>text<?php endif ?>" class="form-control wf-multi-value wf-multi-value-<?php echo $custom_field->id ?> <?php if ($custom_field->type == CustomField::TYPE_DATE): ?>input-date<?php endif ?>" value="<?php echo $value ?>" name="<?php echo $mode ?>[<?php echo $and_i ?>][<?php echo $row_i ?>][value]" disabled/>
                        <?php
                        break;
                }
            }
        }, 20, 6);

        \Eventy::addFilter('workflow.perform_action', function($performed, $type, $operator, $value, $conversation, $workflow) {
            if ($type == 'set_custom_field') {
                $custom_field_id = $operator;
                CustomField::setValue($conversation->id, $custom_field_id, $value);
                return true;
            }

            return $performed;
        }, 20, 6);

        \Eventy::addFilter('workflow.validate_condition', function($has_error, $condition, $workflow) {
            if ($has_error) {
                return $has_error;
            }

            preg_match("/cf_(\d+)/", $condition['type'], $m);
            if (empty($m[1])) {
                return $has_error;
            }
            $custom_field_id = $m[1];
            if ($custom_field_id) {
                if (CustomField::find($custom_field_id)) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return $has_error;
            }
        }, 20, 3);

        \Eventy::addFilter('workflow.validate_action', function($has_error, $action, $workflow) {
            if ($has_error) {
                return $has_error;
            }

            if ($action['type'] != 'set_custom_field') {
                return $has_error;
            }

            if (empty($action['operator'])) {
                return true;
            }

            $custom_field_id = $action['operator'];
        
            if (CustomField::find($custom_field_id)) {
                return false;
            } else {
                return true;
            }
        }, 20, 3);

        // Preload custom fields for all conversations in the table.
        \Eventy::addFilter('conversations_table.preload_table_data', function($conversations) {
            return \CustomField::loadCustomFieldsForConversations($conversations, true, true);
        });

        \Eventy::addAction('conversations_table.after_row', function($conversation, $columns, $col_counter) {
            if (!empty($conversation->custom_fields) && count($conversation->custom_fields)) {
                // Make sure there is a least one field with value.
                $has_non_empty = false;
                foreach ($conversation->custom_fields as $custom_field) {
                    if ($custom_field->value != '') {
                        $has_non_empty = true;
                        break;
                    }
                }
                if (!$has_non_empty) {
                    return;
                }
                echo \View::make('customfields::partials/list_row', [
                    'conversation' => $conversation,
                    'columns' => $columns,
                    'col_counter' => $col_counter,
                ])->render();
            }
        }, 20, 3);

        \Eventy::addAction('conversations_table.row_class', function($conversation) {
            if (!empty($conversation->custom_fields) && count($conversation->custom_fields)) {
                // Make sure there is a least one field with value.
                $has_non_empty = false;
                foreach ($conversation->custom_fields as $custom_field) {
                    if ($custom_field->value != '') {
                        $has_non_empty = true;
                        break;
                    }
                }
                if (!$has_non_empty) {
                    return;
                }
                echo 'conv-row-extended';
            }
        });

        // Add variable.
        \Eventy::addAction('javascript', function($javascripts) {
            $mailbox_id = request()->mailbox_id ?? request()->id ?? 0;
            $custom_fields = \CustomField::getMailboxCustomFields($mailbox_id);
            $cf_vars = [];
            if (count($custom_fields)) {
                foreach ($custom_fields as $custom_field) {
                    $cf_vars['conversation.'.$custom_field->getNameEncoded()] = $custom_field->name.' ('.$custom_field->getNameEncoded().')';
                }
                
                echo 'cfInitVars('.json_encode($cf_vars).');';
            }
        }, 20, 1);

        \Eventy::addFilter('mail_vars.replace', function($vars, $data) {
            if (empty($data['conversation'])) {
                return $vars;
            }
            $custom_fields = CustomField::getCustomFieldsWithValues($data['conversation']->mailbox_id, $data['conversation']->id);

            if (!$custom_fields) {
                return $vars;
            }

            foreach ($custom_fields as $custom_field) {
                $vars['{%conversation.'.$custom_field->getNameEncoded().'%}'] = $custom_field->getAsText();
            }

            return $vars;
        }, 20, 2);

        // When merging conversations if two conversations belong to the same mailbox,
        // merge Custom Fields also.
        \Eventy::addAction('conversation.merged', function($conv1, $conv2) {
            if ($conv1->mailbox_id != $conv2->mailbox_id) {
                return;
            }
            $custom_fields2 = CustomField::getCustomFieldsWithValues($conv2->mailbox_id, $conv2->id);
            if (count($custom_fields2)) {
                $custom_fields1 = CustomField::getCustomFieldsWithValues($conv1->mailbox_id, $conv1->id);

                foreach ($custom_fields2 as $custom_field2) {
                    if (!$custom_field2->isSet()) {
                        continue;
                    }
                    foreach ($custom_fields1 as $custom_field1) {
                        if ($custom_field1->id == $custom_field2->id) {
                            if (!$custom_field1->isSet()) {
                                CustomField::setValue($conv1->id, $custom_field2->id, $custom_field2->value);
                            }
                            break;
                        }
                    }
                }
            }
            
        }, 20, 2);

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
            __DIR__.'/../Config/config.php' => config_path('customfields.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'customfields'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/customfields');

        $sourcePath = __DIR__ . '/../Resources/view';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/customfields';
        }, \Config::get('view.paths')), [$sourcePath]), 'customfields');
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
     *
     * @return void
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
