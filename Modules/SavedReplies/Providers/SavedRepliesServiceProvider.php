<?php

namespace Modules\SavedReplies\Providers;

use App\User;
use Illuminate\Support\ServiceProvider;
//use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\SavedReplies\Entities\SavedReply;

define('SR_MODULE', 'savedreplies');

class SavedRepliesServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    // protected $policies = [
    //     'Modules\SavedReplies\Entities\SavedReply' => 'Modules\SavedReplies\Policies\SavedReplyPolicy',
    // ];

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
        //$this->registerPolicies();
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(SR_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(SR_MODULE).'/js/laroute.js';
            if (!preg_grep("/html5sortable\.js$/", $javascripts)) {
                $javascripts[] = \Module::getPublicPath(SR_MODULE).'/js/html5sortable.js';
            }
            $javascripts[] = \Module::getPublicPath(SR_MODULE).'/js/module.js';

            return $javascripts;
        });
        
        // JS messages
        \Eventy::addAction('js.lang.messages', function() {
            ?>
                "new_saved_reply": "<?php echo __("New Saved Reply") ?>",
                "confirm_delete_saved_reply": "<?php echo __("Delete this Saved Reply?") ?>",
            <?php
        });

        // Add Saved Replies item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            $user = auth()->user();
            if ($user->isAdmin() || $user->hasPermission(User::PERM_EDIT_SAVED_REPLIES)) {
                echo \View::make('savedreplies::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 20);

        // Show saved replies in reply editor
        \Eventy::addAction('reply_form.after', [$this, 'editorDropdown']);
        \Eventy::addAction('new_conversation_form.after', [$this, 'editorDropdown']);

        // Determine whether the user can view mailboxes menu.
        \Eventy::addFilter('user.can_view_mailbox_menu', function($value, $user) {
            return $value || $user->hasPermission(User::PERM_EDIT_SAVED_REPLIES);
        }, 20, 2);

        // Redirect user to the accessible mailbox settings route.
        \Eventy::addFilter('mailbox.accessible_settings_route', function($value, $user, $mailbox) {
            if ($user->hasPermission(User::PERM_EDIT_SAVED_REPLIES) && $mailbox->userHasAccess($user->id)) {
                return 'mailboxes.saved_replies';
            } else {
                return $value;
            }
        }, 20, 3);

        // Select main menu item.
        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['manage']['mailboxes'][] = 'mailboxes.saved_replies';

            return $menu;
        });
    }

    /**
     * Show saved replies in reply editor
     * @param  [type] $conversation [description]
     * @return [type]               [description]
     */
    public function editorDropdown($conversation)
    {
        $saved_replies = SavedReply::where('mailbox_id', $conversation->mailbox->id)
            ->select(['id', 'name', 'parent_saved_reply_id', 'sort_order'])
            //->orderby('sort_order')
            ->get();
        $saved_replies = $saved_replies->sortBy('sort_order');
        echo \View::make('savedreplies::partials/editor_dropdown', ['saved_replies' => $saved_replies])->render();
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
            __DIR__.'/../Config/config.php' => config_path('savedreplies.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'savedreplies'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/savedreplies');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/savedreplies';
        }, \Config::get('view.paths')), [$sourcePath]), 'savedreplies');
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
