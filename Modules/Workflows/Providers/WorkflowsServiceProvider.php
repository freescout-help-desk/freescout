<?php

namespace Modules\Workflows\Providers;

use App\User;
use Illuminate\Support\ServiceProvider;
use Modules\Workflows\Entities\Workflow;
use Modules\Workflows\Http\Controllers\WorkflowsController;
use Modules\Workflows\Listeners\RunWorkflows;
use Modules\Workflows\Services\WorkflowAuthorizer;
use Modules\Workflows\Services\WorkflowHealth;
use Modules\Workflows\Services\WorkflowRunner;

class WorkflowsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([\Modules\Workflows\Console\WorkflowsProcess::class]);
    }

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'workflows');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'workflows');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'workflows');
        \Illuminate\Support\Facades\Event::listen(\App\Events\UserDeleted::class, function ($event) {
            self::deactivateDeletedUser($event);
        });
        self::hooks(new class {
            public function addFilter($hook, $callback, $priority = 20, $arguments = 1)
            {
                \Eventy::addFilter($hook, $callback, $priority, $arguments);
            }

            public function addAction($hook, $callback, $priority = 20, $arguments = 1)
            {
                \Eventy::addAction($hook, $callback, $priority, $arguments);
            }
        });
    }

    /**
     * Register schedule, availability, autoreply, and conversation actions.
     * $events only needs addFilter and addAction.
     *
     * @param object $events
     * @return void
     */
    public static function hooks($events): void
    {
        $events->addFilter('schedule', function ($schedule) {
            $schedule->command('freescout:workflows-process')
                ->everyMinute()
                ->withoutOverlapping();

            return $schedule;
        }, 20, 1);

        $events->addFilter('user.is_user_available', function ($available, $user) {
            return self::userIsAvailable($available, $user);
        }, 20, 2);

        $events->addFilter('autoreply.should_send', function ($send, $conversation) {
            return self::autoreplyShouldSend($send, $conversation);
        }, 20, 2);

        self::listen($events, 'conversation.created_by_customer', 3, true);
        self::listen($events, 'conversation.customer_replied', 3, true);
        self::listen($events, 'conversation.user_replied', 2, true);
        self::listen($events, 'conversation.note_added', 2, true);
        self::listen($events, 'conversation.moved', 3, false);
        self::listen($events, 'conversation.status_changed', 4, false);
        self::listen($events, 'conversation.user_changed', 3, false);
        self::listen($events, 'conversation.subject_changed', 3, false);
        self::listen($events, 'conversation.state_changed', 3, false);

        $events->addAction('customer.deleting', function ($customer) {
            self::deactivateDeletedRecord($customer, 'customer');
        }, 20, 1);

        $events->addAction('mailbox.deleted', function ($mailbox) {
            self::deactivateDeletedRecord($mailbox, 'mailbox');
        }, 20, 1);

        // The name filter only receives the id when it accepts 2 arguments.
        $permissionId = self::workflowsPermissionId();

        $events->addFilter('user_permissions.list', function ($permissions) use ($permissionId) {
            return self::appendWorkflowPermission($permissions, $permissionId);
        }, 20, 1);

        $events->addFilter('user_permissions.name', function ($name, $id) use ($permissionId) {
            if (self::sameWorkflowPermission($id, $permissionId)) {
                return WorkflowAuthorizer::permissionName();
            }

            return $name;
        }, 20, 2);

        $events->addAction('mailboxes.settings.menu', function ($mailbox) {
            self::settingsMenu($mailbox);
        }, 20, 1);

        $events->addAction('conversation.append_action_buttons', function ($conversation, $mailbox) {
            self::conversationMenu($conversation, $mailbox);
        }, 20, 2);

        $events->addAction('bulk_actions.before_delete', function ($mailbox) {
            self::bulkMenu($mailbox);
        }, 20, 1);

        $events->addFilter('settings.sections', function ($sections) {
            return self::settingsSections($sections);
        }, 20, 1);

        $events->addFilter('settings.view', function ($view, $section) {
            return self::settingsView($view, $section);
        }, 20, 2);

        $events->addFilter('settings.section_settings', function ($settings, $section) {
            return self::settingsSectionSettings($settings, $section);
        }, 20, 2);

        $events->addFilter('settings.section_params', function ($params, $section) {
            return self::settingsSectionParams($params, $section);
        }, 20, 2);
    }

    /**
     * Leave a non-array alone. The title is the literal shown in the settings menu.
     *
     * @param mixed $sections
     * @return mixed
     */
    private static function settingsSections($sections)
    {
        if (!is_array($sections)) {
            return $sections;
        }

        $sections['workflows'] = [
            'title' => 'Workflows',
            'icon' => 'random',
            'order' => 400,
        ];

        return $sections;
    }

    /**
     * Two arguments. Any other section keeps the view SettingsController already chose.
     *
     * @param mixed $view
     * @param mixed $section
     * @return mixed
     */
    private static function settingsView($view, $section)
    {
        if ($section === 'workflows') {
            return 'workflows::partials.settings';
        }

        return $view;
    }

    /**
     * Two arguments. Option is read only for the workflows section.
     *
     * @param mixed $settings
     * @param mixed $section
     * @return mixed
     */
    private static function settingsSectionSettings($settings, $section)
    {
        if ($section !== 'workflows') {
            return $settings;
        }

        return [
            'workflows.allow_non_admins' => \Option::get('workflows.allow_non_admins'),
        ];
    }

    /**
     * Two arguments. default false makes an unchecked box Option::remove, not an .env write.
     *
     * @param mixed $params
     * @param mixed $section
     * @return mixed
     */
    private static function settingsSectionParams($params, $section)
    {
        if ($section !== 'workflows') {
            return $params;
        }

        return [
            'settings' => [
                'workflows.allow_non_admins' => ['default' => false],
            ],
        ];
    }

    /**
     * A draft array is not a conversation. Return before allows(), which reads Option for non-admins.
     *
     * @param mixed $conversation
     * @param mixed $mailbox
     * @return void
     */
    private static function conversationMenu($conversation, $mailbox): void
    {
        if (!self::isRecord($conversation) || !self::isRecord($mailbox)) {
            return;
        }

        if (!WorkflowsController::runnerAllowed(auth()->user())) {
            return;
        }

        echo view('workflows::partials.conversation_menu', [
            'conversation' => $conversation,
            'workflows' => self::manualWorkflows($mailbox->id),
        ]);
    }

    /**
     * A draft array is not a mailbox. Return before allows(), which reads Option for non-admins.
     *
     * @param mixed $mailbox
     * @return void
     */
    private static function bulkMenu($mailbox): void
    {
        if (!self::isRecord($mailbox)) {
            return;
        }

        if (!WorkflowsController::runnerAllowed(auth()->user())) {
            return;
        }

        echo view('workflows::partials.bulk_menu', [
            'mailbox' => $mailbox,
            'workflows' => self::manualWorkflows($mailbox->id),
        ]);
    }

    /**
     * @param mixed $record
     * @return bool
     */
    private static function isRecord($record): bool
    {
        return is_object($record) && isset($record->id);
    }

    /**
     * Active manual workflows for this mailbox, in sort_order then id.
     *
     * @param mixed $mailboxId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function manualWorkflows($mailboxId)
    {
        return Workflow::query()
            ->where('mailbox_id', $mailboxId)
            ->where('type', 'manual')
            ->where('active', 1)
            ->with(['actions'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * A draft array is not a mailbox. Return before allows(), which reads Option for non-admins.
     *
     * @param mixed $mailbox
     * @return void
     */
    private static function settingsMenu($mailbox): void
    {
        if (!is_object($mailbox) || !isset($mailbox->id)) {
            return;
        }

        $user = auth()->user();
        if (!is_object($user) || !WorkflowAuthorizer::allows($user)) {
            return;
        }

        echo view('workflows::partials.settings_menu', ['mailbox' => $mailbox]);
    }

    /**
     * config() is missing while the module is inactive. A non-id falls back to 1001.
     *
     * @return int
     */
    private static function workflowsPermissionId()
    {
        $id = config('workflows.permission_id', 1001);
        if (is_int($id)) {
            return $id;
        }
        if (is_string($id) && ctype_digit($id)) {
            return (int) $id;
        }

        return 1001;
    }

    /**
     * Leave a non-array alone. Int 1001 and digit-string "1001" are already present.
     *
     * @param mixed $permissions
     * @param int   $permissionId
     * @return mixed
     */
    private static function appendWorkflowPermission($permissions, $permissionId)
    {
        if (!is_array($permissions)) {
            return $permissions;
        }

        foreach ($permissions as $existing) {
            if (self::sameWorkflowPermission($existing, $permissionId)) {
                return $permissions;
            }
        }

        $permissions[] = $permissionId;

        return $permissions;
    }

    /**
     * @param mixed $value
     * @param int   $permissionId
     * @return bool
     */
    private static function sameWorkflowPermission($value, $permissionId)
    {
        if (is_int($value)) {
            return $value === $permissionId;
        }

        return is_string($value) && ctype_digit($value) && (int) $value === $permissionId;
    }

    /**
     * One argument. A draft array is not a record. A failed lookup must not stop the delete.
     *
     * @param mixed  $record
     * @param string $kind
     * @return void
     */
    private static function deactivateDeletedRecord($record, $kind): void
    {
        if (!is_object($record) || !isset($record->id)) {
            return;
        }

        try {
            WorkflowHealth::deactivateReferencing($record->id, $kind);
        } catch (\Throwable $e) {
            // The customer or mailbox delete continues when workflows cannot be updated.
        }
    }

    /**
     * Listen to the Laravel event only. Its constructor also fires user.deleted.
     *
     * @param mixed $event
     * @return void
     */
    private static function deactivateDeletedUser($event): void
    {
        if (!is_object($event) || !isset($event->deleted_user) || !is_object($event->deleted_user) || !isset($event->deleted_user->id)) {
            return;
        }

        try {
            WorkflowHealth::deactivateReferencing($event->deleted_user->id, 'user');
        } catch (\Throwable $e) {
            // The user delete continues when workflows cannot be updated.
        }
    }

    /**
     * Moved and value-change events have no thread. Pass null rather than the user argument.
     *
     * @param object $events
     * @param string $eventName
     * @param int    $arguments
     * @param bool   $hasThread
     * @return void
     */
    private static function listen($events, $eventName, $arguments, $hasThread): void
    {
        $events->addAction($eventName, function (...$args) use ($eventName, $hasThread) {
            $conversation = array_key_exists(0, $args) ? $args[0] : null;
            $thread = null;
            if ($hasThread && array_key_exists(1, $args)) {
                $thread = $args[1];
            }

            self::runTriggered($eventName, $conversation, $thread);
        }, 20, $arguments);
    }

    /**
     * @param string $eventName
     * @param mixed  $conversation
     * @param mixed  $thread
     * @return void
     */
    private static function runTriggered($eventName, $conversation, $thread): void
    {
        $trigger = RunWorkflows::triggerFor($eventName, $conversation, $thread);
        if ($trigger === null) {
            return;
        }

        WorkflowRunner::runMailbox($conversation, $trigger, $thread);
    }

    /**
     * A filter that already returned false must stay false.
     * An integer id is looked up. A down database returns the incoming value.
     *
     * @param mixed $available
     * @param mixed $user
     * @return mixed
     */
    private static function userIsAvailable($available, $user)
    {
        if ($available !== true) {
            return $available;
        }

        if (is_object($user)) {
            return $user->status == User::STATUS_ACTIVE;
        }

        if (is_int($user) || (is_string($user) && is_numeric($user))) {
            try {
                $found = User::find($user);
            } catch (\Throwable $e) {
                return $available;
            }

            if ($found === null) {
                return false;
            }

            return $found->status == User::STATUS_ACTIVE;
        }

        return false;
    }

    /**
     * Do not call getMeta unless the conversation has that method.
     *
     * @param mixed $send
     * @param mixed $conversation
     * @return mixed
     */
    private static function autoreplyShouldSend($send, $conversation)
    {
        if (!is_object($conversation) || !method_exists($conversation, 'getMeta')) {
            return $send;
        }

        if ($conversation->getMeta('ar_off')) {
            return false;
        }

        return $send;
    }
}
