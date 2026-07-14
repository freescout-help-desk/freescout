<?php

namespace Modules\ArmsReports\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * ARMS reports catalogue (ARMS-13).
 *
 * Adds two report pages (ARMS KPIs, Agent Performance) computed from the
 * conversations/threads tables, plus the launch-critical first_reply_at
 * listener that stamps the column medians rely on.
 *
 * Query logic lives in service classes (Services/) so the December portal
 * phase can expose the same numbers via API without re-implementation.
 */
class ArmsReportsServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'armsreports');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Nav: append the ARMS Reports dropdown after the Manage menu.
        \Eventy::addAction('menu.append', function () {
            $user = auth()->user();
            if ($user && $user->isAdmin()) {
                echo \View::make('armsreports::menu')->render();
            }
        });

        // Launch-critical: stamp first_reply_at on the first agent reply.
        // Medians read this column for new conversations and fall back to
        // deriving from threads for historical ones.
        \Eventy::addAction('conversation.user_replied', function ($conversation, $thread = null) {
            try {
                if (empty($conversation->first_reply_at)) {
                    $conversation->first_reply_at = $thread->created_at ?? now();
                    $conversation->save();
                }
            } catch (\Throwable $e) {
                // Never let reporting bookkeeping break the reply flow.
                \Helper::logException($e, '[ArmsReports] first_reply_at listener');
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
    }
}
