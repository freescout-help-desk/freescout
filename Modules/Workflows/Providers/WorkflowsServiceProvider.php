<?php

namespace Modules\Workflows\Providers;

use App\User;
use Illuminate\Support\ServiceProvider;
use Modules\Workflows\Listeners\RunWorkflows;
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
