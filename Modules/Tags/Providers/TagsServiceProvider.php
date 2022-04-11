<?php

namespace Modules\Tags\Providers;

use App\Conversation;
use App\User;
use Modules\Tags\Entities\ConversationTag;
use Modules\Tags\Entities\Tag;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('TAGS_MODULE', 'tags');

class TagsServiceProvider extends ServiceProvider
{
    public static $wf_tag_attached = null;

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
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(TAGS_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(TAGS_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(TAGS_MODULE).'/js/module.js';
            return $javascripts;
        });

        // Show tags button in conversation
        \Eventy::addAction('conversation.action_buttons', function($conversation, $mailbox) {
            echo \View::make('tags::partials/action_button', ['conversation' => $conversation])->render();
        }, 20, 2);

        // Show tags next to the conversation title in conversation
        \Eventy::addAction('conversation.after_subject', function($conversation, $mailbox) {
            $tags = Tag::conversationTags($conversation);
            echo \View::make('tags::partials/subject_tags', ['tags' => $tags])->render();
        }, 20, 2);

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view') || \Route::is('conversations.create')) {
                echo 'initConvTags("'.__('Remove Tag').'");';
            } else {
                echo 'initTagsBulk();';
            }
        });

        // Preload tags for all conversations in the table
        \Eventy::addFilter('conversations_table.preload_table_data', function($conversations) {
            $ids = $conversations->pluck('id')->unique()->toArray();
            if (!$ids) {
                return $conversations;
            }

            $conversations_tags = ConversationTag::whereIn('conversation_id', $ids)->get();
            if (!count($conversations_tags)) {
                return $conversations;
            }
            $tag_ids = $conversations_tags->pluck('tag_id')->unique()->toArray();

            $tags = Tag::whereIn('id', $tag_ids)->get();
            if (!count($tags)) {
                return $conversations;
            }

            foreach ($conversations as $i => $conversation) {
                // Find conversation tags
                $collected_tags = [];
                foreach ($conversations_tags as $conversation_tag) {
                    if ($conversation_tag->conversation_id == $conversation->id) {
                        $collected_tags[] = $tags->find($conversation_tag->tag_id);
                    }
                }
                $conversation->tags = $collected_tags;
            }

            return $conversations;
        });

        // Show tags in the conersations table
        \Eventy::addAction('conversations_table.before_subject', function($conversation) {
            // Show conversation tags
            if (!empty($conversation->tags)) {
                echo '<span class="conv-tags">';
                foreach ($conversation->tags as $i => $tag) {
                    echo \View::make('tags::partials/conversation_list_tag', ['tag' => $tag])->render();
                    if ($i != count($conversation->tags)-1) {
                        echo ' ';
                    }
                }
                echo '</span>&nbsp;';
            }
        });

        // Filter by tag in search
        \Eventy::addFilter('search.conversations.apply_filters', function($query_conversations, $filters) {

            if (!empty($filters['tag'])) {
                $tag_names = [];
                if (is_array($filters['tag'])) {
                    foreach ($filters['tag'] as $tag_name) {
                        $tag_name = Tag::normalizeName($tag_name);
                        if ($tag_name) {
                            $tag_names[] = $tag_name;
                        }
                    }
                } else {
                    $tag_name = Tag::normalizeName($filters['tag']);
                    if ($tag_name) {
                        $tag_names[] = $tag_name;
                    }
                }

                if ($tag_names) {
                    $query_conversations
                        ->join('conversation_tag', function ($join) {
                            $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
                        })
                        ->join('tags', function ($join) {
                            $join->on('tags.id', '=', 'conversation_tag.tag_id');
                        })
                        //->whereIn('tags.name', $tag_names);
                        ->where('tags.name', $tag_names);
                }
            }

            return $query_conversations;
        }, 20, 2);

        // Add tags to search filters.
        \Eventy::addFilter('search.filters_list', function($filters_list, $mode, $filters, $q) {

            if ($mode != Conversation::SEARCH_MODE_CONV) {
                return $filters_list;
            }

            // Add after subject.
            foreach ($filters_list as $i => $filter) {
                if ($filter == 'subject') {
                    array_splice($filters_list, $i+1, 0, 'tag');
                    break;
                }
            }

            return $filters_list;
        }, 20, 4);

        // Add tags to search filters.
        \Eventy::addAction('search.display_filters', function($filters, $filters_data) {
            ?>
                <div class="col-sm-6 form-group <?php if (isset($filters['tag'])): ?> active <?php endif ?>" data-filter="tag">
                    <label><?php echo __('Tag') ?> <b class="remove" data-toggle="tooltip" title="<?php echo __('Remove filter') ?>">×</b></label>
                    <input type="text" name="f[tag]" value="<?php echo ($filters['tag'] ?? '') ?>" class="form-control" <?php if (empty($filters['tag'])): ?> disabled <?php endif ?>>
                </div>
            <?php
        }, 20, 2);

        // Workflows.
        \Eventy::addFilter('workflows.conditions_config', function($conditions) {
            $conditions['conversation']['items']['tag'] = [
                'title' => __('Tag(s)'),
                'operators' => [
                    'equal' => __('Is equal to'),
                    'contains' => __('Contains'),
                    'not_contains' => __('Does not contain'),
                    'regex' => __('Matches regex pattern'),
                ],
                'triggers' => [
                    'tag.attached',
                ]
            ];
            return $conditions;
        });

        \Eventy::addAction('tag.attached', function($tag, $conversation_id) {
            if (!\Module::isActive('workflows')) {
                return;
            }
            $conversation = Conversation::find($conversation_id);
            if ($conversation) {
                self::$wf_tag_attached = $tag;
                \Workflow::runAutomaticForConversation($conversation, 'tag.attached');
                self::$wf_tag_attached = null;
            }
        }, 20, 2);

        \Eventy::addFilter('workflow.check_condition', function($result, $type, $operator, $value, $conversation, $workflow) {
            if ($type != 'tag') {
                return $result;
            }
            if (self::$wf_tag_attached) {
                $conversation_tags = [self::$wf_tag_attached->name];
            } else {
                $conversation_tags = Tag::conversationTags($conversation)->pluck('name')->toArray();
            }

            $value_tags = explode(',', $value);
            foreach ($value_tags as $value) {
                $value = trim($value);
                $result = \Workflow::compareArray($conversation_tags, $value, $operator);
                if ($result) {
                    return true;
                }
            }
            return false;
        }, 20, 6);

        \Eventy::addFilter('workflows.actions_config', function($actions) {
            $actions['dummy']['items']['add_tag'] = [
                'title' => __('Add Tag(s)'),
            ];
            $actions['dummy']['items']['remove_tag'] = [
                'title' => __('Remove Tag(s)'),
            ];
            return $actions;
        });

        \Eventy::addFilter('workflow.perform_action', function($performed, $type, $operator, $value, $conversation, $workflow) {
            if ($type == 'add_tag') {
                $value_tags = explode(',', $value);
                foreach ($value_tags as $tag_name) {
                    $tag_name = trim($tag_name);
                    Tag::attachByName($tag_name, $conversation->id);
                }
                return true;
            }

            if ($type == 'remove_tag') {
                $value_tags = explode(',', $value);
                foreach ($value_tags as $tag_name) {
                    $tag_name = trim($tag_name);
                    Tag::detachByName($tag_name, $conversation->id);
                }
                return true;
            }

            return $performed;
        }, 20, 6);

        // Do not 
        \Eventy::addFilter('tag.can_delete', function($can_delete, $tag, $conversation_id) {
            if (!$can_delete) {
                return $can_delete;
            }
            return !\Module::isActive('workflows');
        }, 20, 3);

        // In bulk actions.
        \Eventy::addAction('bulk_actions.before_delete', function($mailbox) {
            ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-default conv-add-tags" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?php echo __("Tag") ?>">
                        <span class="glyphicon glyphicon-tag"></span>
                    </button>
                    <ul class="dropdown-menu" id="add-tag-wrap">
                        <li><div class="input-group">
                            <select class="form-control tag-input" multiple/>
                            </select>
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button"><i class="glyphicon glyphicon-ok"></i></button>
                            </span>
                        </div></li>
                    </ul>
                </div>
            <?php   
        });

        // On conversation create.
        \Eventy::addAction('conversation.create_form.subject_append', function($mailbox) {
            ?>
                <i class="conv-new-tag conv-add-tags glyphicon glyphicon-tag" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"></i>
                <ul class="dropdown-menu dropdown-menu-right conv-new-tag-dd" id="add-tag-wrap">
                    <li><div class="input-group">
                        <select class="form-control tag-input" multiple/>
                        </select>
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="button"><i class="glyphicon glyphicon-ok"></i></button>
                        </span>
                    </div></li>
                </ul>
            <?php   
        });

        // Show tags next to the conversation title in conversation
        \Eventy::addAction('conversation.create_form.after_subject', function($conversation, $mailbox) {
            $tags = Tag::conversationTags($conversation);
            
            ?>
            <div>
                <div class="col-sm-offset-2 col-sm-9">
                    <?php echo \View::make('tags::partials/subject_tags', ['tags' => $tags])->render(); ?>
                    <div class="clearfix"></div>
                </div>
            </div>
            <?php

        }, 40, 2);

        // Add item to the menu
        \Eventy::addFilter('menu.manage.can_view', function($value) {
            if ($value) {
                return $value;
            }

            $user = auth()->user();
            if ($user->isAdmin() || $user->hasPermission(User::PERM_EDIT_TAGS)) {
                return true;
            }

            return $value;
        });

        // Add item to the menu
        \Eventy::addAction('menu.manage.after_mailboxes', function() {
            ?>
                <li class="<?php echo \Helper::menuSelectedHtml('tags') ?>"><a href="<?php echo route('tags.tags') ?>"><?php echo __('Tags') ?></a></li>
            <?php
        }, 20);

        // Select main menu item.
        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['manage']['tags'] = [
                'tags.tags'
            ];

            return $menu;
        });

        // Update tag counters when conversations deleted.
        \Eventy::addAction('conversations.before_delete_forever', function($conversation_ids) {
            $conversations_tags = ConversationTag::whereIn('conversation_id', $conversation_ids)->get();

            foreach ($conversations_tags as $conversation_tag) {
                $tag = $conversation_tag->tag;
                $tag->counter--;
                $tag->save();
            }
        }, 20, 1);
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
            __DIR__.'/../Config/config.php' => config_path('tags.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'tags'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/tags');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/tags';
        }, \Config::get('view.paths')), [$sourcePath]), 'tags');
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
