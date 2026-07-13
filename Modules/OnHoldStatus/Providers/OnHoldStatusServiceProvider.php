<?php

namespace Modules\OnHoldStatus\Providers;

use App\Conversation;
use App\Thread;
use Illuminate\Support\ServiceProvider;

/**
 * Adds On-Hold as a first-class conversation status (ARMS-12).
 *
 * Core hard-codes four statuses (Active/Pending/Closed/Spam) but exposes them
 * as mutable static arrays that Blade dropdowns, validation and JS all read
 * from — so appending a fifth status here propagates everywhere except
 * statusCodeToName(), which is covered by the conversation.status_name
 * filter added to core on the threls fork (see ARMS-12).
 *
 * Status mapping for ARMS needs only this one new status:
 * New = Active+unassigned · Open = Active+assigned · Pending = Pending ·
 * On-Hold = 5 (this module) · Solved = Closed.
 */
class OnHoldStatusServiceProvider extends ServiceProvider
{
    /**
     * Status code 5 is free on both models: Conversation reserves it as a
     * commented-out STATUS_OPEN, and Thread::STATUS_NOCHANGE already took 6.
     */
    const STATUS_ONHOLD = 5;

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
        $this->registerStatus();
        $this->hooks();
    }

    /**
     * Register the On-Hold status with core's status registries.
     */
    protected function registerStatus()
    {
        Conversation::$statuses[self::STATUS_ONHOLD] = 'onhold';
        Conversation::$status_icons[self::STATUS_ONHOLD] = 'pause';
        Conversation::$status_classes[self::STATUS_ONHOLD] = 'warning';
        Conversation::$status_colors[self::STATUS_ONHOLD] = '#f39c12';

        // Status codes must match between conversations and threads
        // (see the comment above Conversation's status constants).
        Thread::$statuses[self::STATUS_ONHOLD] = 'onhold';
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Resolves the status name for both Conversation::statusCodeToName()
        // and Thread::statusCodeToName() (fork patch, ARMS-12).
        \Eventy::addFilter('conversation.status_name', function ($name, $status) {
            if ((int) $status === self::STATUS_ONHOLD) {
                return __('On Hold');
            }

            return $name;
        }, 20, 2);

        // The Mine folder and chat list are live queries with an "open statuses"
        // whitelist (Active/Pending) rather than real folders — without this,
        // On-Hold conversations vanish from Mine (fork patch, ARMS-12).
        \Eventy::addFilter('conversation.open_statuses', function ($statuses) {
            $statuses = (array) $statuses;
            $statuses[] = self::STATUS_ONHOLD;

            return $statuses;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
