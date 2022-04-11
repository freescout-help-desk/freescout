<?php

namespace Modules\CustomFolders\Providers;

use App\Conversation;
use App\Folder;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('CFOLDERS_MODULE', 'customfolders');

class CustomFoldersServiceProvider extends ServiceProvider
{
    const TYPE_CUSTOM = 200; // max 255

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
        // Add module's CSS file to the application layout.
        // \Eventy::addFilter('stylesheets', function($styles) {
        //     $styles[] = \Module::getPublicPath(CF_MODULE).'/css/module.css';
        //     return $styles;
        // });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(CFOLDERS_MODULE).'/js/laroute.js';
            if (!preg_grep("/html5sortable\.js$/", $javascripts)) {
                $javascripts[] = \Module::getPublicPath(CFOLDERS_MODULE).'/js/html5sortable.js';
            }
            $javascripts[] = \Module::getPublicPath(CFOLDERS_MODULE).'/js/module.js';

            return $javascripts;
        });

        \Eventy::addFilter('mailbox.folders.public_types', function($list) {
            $list[] = self::TYPE_CUSTOM;

            return $list;
        }, 20, 1);

         \Eventy::addFilter('folder.type_icon', function($icon, $folder) {
             if ($folder->type == self::TYPE_CUSTOM) {
                 return $folder->meta['icon'] ?? '';
             }

             return $icon;
         }, 20, 2);

        \Eventy::addFilter('folder.type_name', function($name, $folder) {
            if ($folder->type == self::TYPE_CUSTOM) {
                return $folder->meta['name'] ?? '';
            }

            return $name;
        }, 20, 2);

        // \Eventy::addFilter('folder.waiting_since_query', function($query, $folder) {
        //     if ($folder->type != self::TYPE_CUSTOM) {
        //         return $query;
        //     }

        //     $tag_id = $folder->meta['tag_id'] ?? null;
        //     $query = Conversation::where('conversations.mailbox_id', $folder->mailbox_id)
        //             ->join('conversation_tag', 'conversations.id', '=', 'conversation_tag.conversation_id')
        //             ->where('conversation_tag.tag_id', $tag_id);

        //     return $query;
        // }, 20, 2);

        \Eventy::addFilter('folder.conversations_query', function($query, $folder, $user_id) {
            if ($folder->type != self::TYPE_CUSTOM) {
                return $query;
            }

            $tag_id = $folder->meta['tag_id'] ?? 0;

            $query = Conversation::select('conversations.*')
                ->where('mailbox_id', $folder->mailbox_id)
                ->where('state', Conversation::STATE_PUBLISHED);
            if ($tag_id) {
                $query->join('conversation_tag', 'conversations.id', '=', 'conversation_tag.conversation_id')
                    ->where('conversation_tag.tag_id', $tag_id);
            }

            if (!empty($folder->meta['own_only'])) {
                $query->where('conversations.user_id', Auth::id());
            }

            self::applyStatusFilter($query, $folder);

            return $query;
        }, 20, 3);

        \Eventy::addFilter('folder.conversations_order_by', function($order_by, $folder_type) {
            if ($folder_type != self::TYPE_CUSTOM) {
                return $order_by;
            }
            $order_by[] = ['status' => 'asc'];
            $order_by[] = ['last_reply_at' => 'desc'];

            return $order_by;
        }, 20, 2);

        \Eventy::addFilter('conversation.is_in_folder_allowed', function($is_allowed, $folder) {
            if ($folder->type != self::TYPE_CUSTOM) {
                return $is_allowed;
            }

            // todo: maybe check tag assignment.
            return true;
        }, 20, 2);

        \Eventy::addFilter('conversation.get_nearby_query', function($query, $conversation, $mode, $folder) {
            if ($folder->type != self::TYPE_CUSTOM) {
                return $query;
            }

            $tag_id = $folder->meta['tag_id'] ?? 0;

            $query = Conversation::select('conversations.*')
                ->where('conversations.mailbox_id', $conversation->mailbox_id)
                ->where('conversations.id', '<>', $conversation->id);

            if ($tag_id) {
                $query->join('conversation_tag', 'conversations.id', '=', 'conversation_tag.conversation_id')
                    ->where('conversation_tag.tag_id', $tag_id);
            }

            return $query;
        }, 20, 4);

        \Eventy::addFilter('folder.update_counters', function($updated, $folder) {
            if ($folder->type != self::TYPE_CUSTOM) {
                return $updated;
            }

            return self::setCounters($folder);
        }, 20, 2);

        \Eventy::addFilter('folder.counter', function($counter, $folder, $folders) {
            if ($folder->type != self::TYPE_CUSTOM) {
                return $counter;
            }

            return $folder->meta['counter'] ?? Folder::COUNTER_TOTAL;
        }, 20, 3);

        \Eventy::addFilter('folder.count', function($count, $folder, $counter, $folders) {
            if ($folder->type != self::TYPE_CUSTOM) {
                return $count;
            }

            if (isset($folder->meta['own_only']) && $folder->meta['own_only']) {
                // echo "<pre>";
                // print_r($folder->meta);
                // exit();
                $user_id = Auth::id();
                if (
                    !empty($folder->meta['counts']) && !empty($folder->meta['counts'][$user_id])
                    && isset($folder->meta['counts'][$user_id][$counter])
                ) {
                    $count = $folder->meta['counts'][$user_id][$counter];
                }
            }

            return $count;
        }, 20, 4);

        // Sort custom folders.
        \Eventy::addFilter('mailbox.folders', function($folders) {
            foreach ($folders as $i => $folder) {
                if ($folder->type != self::TYPE_CUSTOM) {
                    continue;
                }
                $user_id = Auth::id();
                if (!empty($folder->user_id) && $folder->user_id != $user_id) {
                    unset($folders[$i]);
                }
            }
            return self::sortFolders($folders);
        }, 20, 3);

        \Eventy::addAction('tag.attached', function($tag, $conversation_id) {
            self::onAttachDetach($tag, $conversation_id);
        }, 20, 2);

        \Eventy::addAction('tag.detached', function($tag, $conversation_id) {
            self::onAttachDetach($tag, $conversation_id);
        }, 20, 2);

        \Eventy::addFilter('tag.can_delete', function($can_delete, $tag, $conversation_id) {
            if (!$can_delete) {
                return $can_delete;
            }
            $folders = Folder::where('type', \CustomFolder::TYPE_CUSTOM)->get();
            foreach ($folders as $folder) {
                if (!empty($folder->meta['tag_id']) && $folder->meta['tag_id'] == $tag->id) {
                    return false;
                }
            }
            return true;
        }, 20, 3);

        // Add Custom Folders item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (self::canUserUpdateMailboxCustomFolders($mailbox)) {
                echo \View::make('customfolders::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 12);

        // Determine whether the user can view mailboxes menu.
        \Eventy::addFilter('user.can_view_mailbox_menu', function($value, $user) {
            return $value || $user->hasPermission(User::PERM_EDIT_CUSTOM_FOLDERS);
        }, 20, 2);

        // Redirect user to the accessible mailbox settings route.
        \Eventy::addFilter('mailbox.accessible_settings_route', function($value, $user, $mailbox) {
            if ($value) {
                return $value;
            }
            if (self::canUserUpdateMailboxCustomFolders($mailbox, $user)) {
                return 'mailboxes.custom_folders';
            } else {
                return $value;
            }
        }, 20, 3);
    }

    public static function canUserUpdateMailboxCustomFolders($mailbox, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        return $user->isAdmin() || ($user->hasPermission(User::PERM_EDIT_CUSTOM_FOLDERS) && $mailbox->userHasAccess($user->id));
    }

    public static function applyStatusFilter($query, $folder)
    {
        if (isset($folder->meta['status_filter']) && !empty($folder->meta['status_filter']) 
            && is_array($folder->meta['status_filter'])
        ) {
            $query->whereIn('status', $folder->meta['status_filter']);
        }
    }

    public static function onAttachDetach($tag, $conversation_id)
    {
        $folders = self::getFoldersByConversation($conversation_id);
        foreach ($folders as $folder) {
            if (!empty($folder->meta['tag_id']) && $folder->meta['tag_id'] == $tag->id) {
                $folder->updateCounters();
            }
        }
    }

    public static function getFoldersByConversation($conversation_id)
    {
        $folders = [];

        $conversation = Conversation::find($conversation_id);
        if ($conversation) {
            $folders = self::mailboxCustomFolders($conversation->mailbox_id, false);
        }

        return $folders;
    }

    public static function sortFolders($folders)
    {
        return $folders->sortBy(function($folder) {
            if ($folder->type != self::TYPE_CUSTOM) {
                return $folder->type;
            }
            if (!empty($folder->meta['order'])) {
                return (int)$folder->meta['order']+1000;
            } else {
                return 0;
            }
        });
    }

    public static function mailboxCustomFolders($mailbox_id, $sort = true)
    {
        $folders = Folder::where('mailbox_id', $mailbox_id)
            ->where('type', \CustomFolder::TYPE_CUSTOM)->get();

        if ($sort) {
            $folders = \CustomFolder::sortFolders($folders);
        }

        return $folders;
    }

    public static function setCounters($folder)
    {
        $tag_id = $folder->meta['tag_id'] ?? 0;

        $query = Conversation::where('conversations.mailbox_id', $folder->mailbox_id)
            ->where('conversations.state', Conversation::STATE_PUBLISHED);

        if ($tag_id) {
            $query->join('conversation_tag', 'conversations.id', '=', 'conversation_tag.conversation_id')
                ->where('conversation_tag.tag_id', $tag_id);
        }

        if (!empty($folder->user_id)) {
            $query->where('conversations.user_id', $folder->user_id);
        }

        $active_query = clone $query;
        $folder->active_count = $active_query
            ->where('conversations.status', Conversation::STATUS_ACTIVE)
            ->count();

        $total_query = clone $query;

        self::applyStatusFilter($total_query, $folder);
        $folder->total_count = $total_query
            ->count();

        if (
            isset($folder->meta['own_only']) && $folder->meta['own_only']
            && empty($folder->user_id)
        ) {
            $meta = $folder->meta;
            $meta['counts'] = [];

            $user_ids = $folder->mailbox->usersHavingAccess(true)->pluck('id');
            foreach ($user_ids as $user_id) {
                $active_query2 = clone $active_query;
                $total_query2 = clone $total_query;

                $meta['counts'][$user_id] = [
                    Folder::COUNTER_ACTIVE => $active_query2
                        ->where('conversations.user_id', $user_id)
                        ->count(),
                    Folder::COUNTER_TOTAL => $total_query2
                        ->where('conversations.user_id', $user_id)
                        ->count(),
                ];
            }

            $folder->meta = $meta;
        } elseif (isset($meta['counts'])) {
            unset($meta['counts']);
        }

        $folder->save();

        return true;
    }

    public static function isTagsActive()
    {
        return class_exists(\Modules\Tags\Entities\Tag::class);
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
            __DIR__.'/../Config/config.php' => config_path('customfolders.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'customfolders'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/customfolders');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/customfolders';
        }, \Config::get('view.paths')), [$sourcePath]), 'customfolders');
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
