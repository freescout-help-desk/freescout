<?php

namespace Modules\ActiveToNewLabel\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * The actual "Active" -> "New" rename lives in resources/lang/en.json — a
 * plain translation override, since every core call site (status dropdown,
 * bulk actions, thread history, notification emails) already resolves the
 * label through Laravel's translator with no other logic to touch.
 *
 * This module exists only for the one piece that override can't reach
 * cleanly: the paid Workflows module reuses the exact same "Active" string
 * for something unrelated (its own rule-enabled checkbox), so a runtime
 * patch command keeps that checkbox from being swept up by the rename.
 */
class ActiveToNewLabelServiceProvider extends ServiceProvider
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
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            \Modules\ActiveToNewLabel\Console\PatchWorkflowsActiveLabel::class,
        ]);
    }
}
