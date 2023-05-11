<?php

namespace Modules\Workflows\Providers;

use App\Thread;
use Modules\Workflows\Entities\Workflow;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('WF_MODULE', 'workflows');

class WorkflowsServiceProvider extends ServiceProvider
{
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
            $styles[] = \Module::getPublicPath(WF_MODULE).'/css/module.css';
            return $styles;
        });
        
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(WF_MODULE).'/js/laroute.js';
            if (!preg_grep("/html5sortable\.js$/", $javascripts)) {
                $javascripts[] = \Module::getPublicPath(WF_MODULE).'/js/html5sortable.js';
            }
            $javascripts[] = \Module::getPublicPath(WF_MODULE).'/js/module.js';
            return $javascripts;
        });

        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (\Workflow::canEditWorkflows()) {
                echo \View::make('workflows::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 25);

        // Determine whether the user can view mailboxes menu.
        \Eventy::addFilter('user.can_view_mailbox_menu', function($value, $user) {
            return $value || \Workflow::canEditWorkflows();
        }, 20, 2);

        // Redirect user to the accessible mailbox settings route.
        \Eventy::addFilter('mailbox.accessible_settings_route', function($value, $user, $mailbox) {
            if ($value) {
                return $value;
            }
            if (\Workflow::canEditWorkflows(null, $mailbox->id)) {
                return 'mailboxes.workflows';
            } else {
                return $value;
            }
        }, 20, 3);
        
        // Fix mailbox menu route name
        \Eventy::addFilter('mailboxes.menu_current_route', function($route_name) {
            if ($route_name == 'mailboxes.workflows.update') {
                return 'mailboxes.workflows';
            }
            return $route_name;
        }, 25);

        // Fix mailbox menu route name
        \Eventy::addAction('conversation.prepend_action_buttons', function($conversation, $mailbox) {
            $workflow = Workflow::where('mailbox_id', $mailbox->id)
                ->where('active', true)
                ->where('type', Workflow::TYPE_MANUAL)
                ->first();
            if ($workflow) {
                ?>
                    <li><a href="<?php echo route('mailboxes.workflows.ajax_html', ['action' => 'run', 'mailbox_id' => $mailbox->id]) ?>" data-trigger="modal" title="<?php echo __('Run Workflow') ?>" data-modal-size="sm" data-modal-title="<?php echo __('Run Workflow') ?>" data-modal-no-footer="true" data-modal-on-show="initRunWorkflow"><i class="glyphicon glyphicon-random"></i> <?php echo __("Workflow") ?></a></li>
                <?php
            }
        }, 20, 2);

        // Schedule background processing
        \Eventy::addFilter('schedule', function($schedule) {
            $schedule->command('freescout:workflows-process')
                ->cron(config('workflows.process_cron'));

            return $schedule;
        });

        // Show action description for the line item thread.
        \Eventy::addFilter('thread.action_text', function($did_this, $thread, $conversation_number, $escape) {
            if ($thread->action_type == Workflow::ACTION_TYPE_AUTOMATIC_WORKFLOW 
                || $thread->action_type == Workflow::ACTION_TYPE_MANUAL_WORKFLOW
            ) {
                $meta = $thread->getMetas();
                $workflow_id = $meta['workflow_id'] ?? '';
                $workflow = Workflow::find($workflow_id);
                
                if ($workflow) {
                    $user = auth()->user();

                    if (\Workflow::canEditWorkflows($user)) {
                        $workflow_placeholder = '<a href="'.$workflow->url().'">'.htmlspecialchars($workflow->name).'</a>';
                    } else {
                        $workflow_placeholder = '<strong>'.htmlspecialchars($workflow->name).'</strong>';
                    }

                    if ($thread->action_type == Workflow::ACTION_TYPE_AUTOMATIC_WORKFLOW) {
                        if ($conversation_number) {
                            $did_this = __('Workflow :workflow was triggered for conversation #:conversation_number', ['workflow' => $workflow_placeholder, 'conversation_number' => $conversation_number]);
                        } else {
                            $did_this = __('Workflow :workflow was triggered', ['workflow' => $workflow_placeholder]);
                        }
                    } else {
                        $person = $thread->getActionPerson($conversation_number);
                        if ($escape) {
                            $person = htmlspecialchars($person);
                        }
                        if ($conversation_number) {
                            $did_this = __(':person ran the :workflow workflow for conversation #:conversation_number', ['workflow' => $workflow_placeholder, 'conversation_number' => $conversation_number]);
                        } else {
                            $did_this = __(':person ran the :workflow workflow', ['workflow' => $workflow_placeholder, 'person' => $person]);
                        }
                    }
                }
            }
            return $did_this;
        }, 20, 4);

        // On thread create.
        \Eventy::addAction('thread.meta', function($thread) {
            if (!in_array($thread->type, [Thread::TYPE_MESSAGE, Thread::TYPE_NOTE]) || empty($thread->meta['workflow_id'])) {
                return;
            }
            $workflow = Workflow::findCached($thread->meta['workflow_id']);
            if (!$workflow) {
                return;
            }
            $user = auth()->user();
            if (\Workflow::canEditWorkflows($user)) {
                $workflow_placeholder = '<a href="'.$workflow->url().'">'.htmlspecialchars($workflow->name).'</a>';
            } else {
                $workflow_placeholder = '<strong>'.htmlspecialchars($workflow->name).'</strong>';
            }
            ?>
            <div class='thread-meta'><i class="glyphicon glyphicon-random"></i> <?php echo __("Triggered by the :workflow workflow", ['workflow' => $workflow_placeholder]) ?></div>
            <?php
        });

        // On thread create.
        \Eventy::addAction('conversation.user_replied', function($conversation, $thread) {
            if ($thread->created_by_user_id != Workflow::getUser()->id) {
                Workflow::runAutomaticForConversation($thread->conversation, 'conversation.user_replied');
            }
        }, 20, 2);

        \Eventy::addAction('conversation.note_added', function($conversation, $thread) {
            if ($thread->created_by_user_id != Workflow::getUser()->id) {
                Workflow::runAutomaticForConversation($thread->conversation, 'conversation.note_added');
            }
        }, 20, 2);

        \Eventy::addAction('conversation.customer_replied', function($conversation, $thread, $customer) {
            if ($thread->created_by_user_id != Workflow::getUser()->id) {
                Workflow::runAutomaticForConversation($thread->conversation, 'conversation.customer_replied');
            }
        }, 20, 3);

        \Eventy::addAction('conversation.created_by_user', function($conversation, $thread) {
            if ($thread->created_by_user_id != Workflow::getUser()->id) {
                Workflow::runAutomaticForConversation($conversation, 'conversation.created_by_user');
            }
        }, 20, 2);

        \Eventy::addAction('conversation.created_by_customer', function($conversation, $thread) {
            Workflow::runAutomaticForConversation($conversation, 'conversation.created_by_customer');
        }, 20, 2);

        \Eventy::addAction('conversation.status_changed', function($conversation, $user, $changed_on_reply, $prev_status) {
            if ($user->id != Workflow::getUser()->id) {
                Workflow::runAutomaticForConversation($conversation, 'conversation.status_changed');
            }
        }, 20, 4);

        \Eventy::addAction('conversation.state_changed', function($conversation, $user, $prev_status) {
            if ($user->id != Workflow::getUser()->id) {
                Workflow::runAutomaticForConversation($conversation, 'conversation.state_changed');
            }
        }, 20, 3);

        \Eventy::addAction('conversation.user_changed', function($conversation, $user, $prev_user_id) {
            if ($user->id != Workflow::getUser()->id) {
                Workflow::runAutomaticForConversation($conversation, 'conversation.user_changed');
            }
        }, 20, 3);

        \Eventy::addAction('conversation.moved', function($conversation, $user, $prev_mailbox) {
            Workflow::runAutomaticForConversation($conversation, 'conversation.moved');
        }, 20, 3);

        \Eventy::addAction('thread.opened', function($thread, $conversation) {
            Workflow::runAutomaticForConversation($conversation, 'thread.opened');
        }, 20, 2);

        // Process workflow in background
        \Eventy::addAction('workflow.do_process', function($id) {
            $workflow = Workflow::find($id);

            if ($workflow && $workflow->active && $workflow->apply_to_prev) {
                $workflow->processForMailbox();
            }
        });

        // Process user deletion
        \Eventy::addAction('user.deleted', function($deleted_user, $by_user) {
            Workflow::checkAll();
        }, 20, 2);

        // Process mailbox deletion
        \Eventy::addAction('mailbox.before_delete', function($mailbox) {
            Workflow::checkAll();
        });

        \Eventy::addAction('custom_field.before_delete', function($custom_field) {
            Workflow::checkAll();
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            if (!\Route::is('conversations.view')) {
                echo 'initWorkflowsBulk();';
            }
        });

        // In bulk actions.
        \Eventy::addAction('bulk_actions.before_delete', function($mailbox) {
            if (!$mailbox) {
                return;
            }
            $workflows = Workflow::where('mailbox_id', $mailbox->id)
                ->where('active', true)
                ->where('type', Workflow::TYPE_MANUAL)
                ->orderBy('sort_order')
                ->get();
            if (!$workflows) {
                return;
            }
            ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-default" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?php echo __("Workflow") ?>">
                        <span class="glyphicon glyphicon-random"></span>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" id="bulk-workflow-list">
                        <?php foreach($workflows as $workflow): ?>
                            <li>
                                <a href="#" data-wf-id="<?php echo $workflow->id ?>" data-loading-text="<?php echo $workflow->name ?>…"><?php echo $workflow->name ?></a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php   
        }, 40, 1);

        /**
         * Now it's not needed as we are using Thread::META_CONVERSATION_HISTORY meta.
         * Email the Customer - we send only WF message.
         */
        // \Eventy::addFilter('jobs.send_reply_to_customer.send_previous_messages', function($send_previous_messages, $last_thread, $threads, $conversation, $customer) {
        //     if ($last_thread->created_by_user_id == Workflow::getUser()->id && !$last_thread->isForwarded()) {
        //         return false;
        //     }
        //     return $send_previous_messages;
        // }, 20, 5);

        \Eventy::addFilter('reply_email.include_signature', function($value, $thread) {
            if ($thread->created_by_user_id != Workflow::getUser()->id || empty($thread->meta['workflow_id'])) {
                return $value;
            }
            $workflow = Workflow::find($thread->meta['workflow_id']);

            if ($workflow) {
                // Get first email_customer action.
                foreach ($workflow->actions as $ands) {
                    foreach ($ands as $action) {
                        if ($action['type'] == 'email_customer') {
                            $value = json_decode($action['value'] ?? '', true);

                            if (empty($value['no_signature'])) {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    }
                }
            }

            return $value;
        }, 20, 2);


        \Eventy::addFilter('user_permissions.list', function($list) {
            $list[] = \Workflow::PERM_EDIT_WORKFLOWS;
            return $list;
        });

        \Eventy::addFilter('user_permissions.name', function($name, $permission) {
            if ($permission != \Workflow::PERM_EDIT_WORKFLOWS) {
                return $name;
            }
            return __('Users are allowed to manage workflows');
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
            __DIR__.'/../Config/config.php' => config_path('workflows.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'workflows'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/workflows');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/workflows';
        }, \Config::get('view.paths')), [$sourcePath]), 'workflows');
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
            \Modules\Workflows\Console\Process::class
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
