<?php

namespace Modules\EmailCommands\Providers;

use App\Events\UserAddedNote;
use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Thread;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class EmailCommandsServiceProvider extends ServiceProvider
{
    public static $commands = [
        'note',
        'assign',
        'me',
        'status',
        'active',
        'pending',
        'closed',
        'spam',
        'tag',
        //'subject', // todo
    ];

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
        // Process incoming message
        \Eventy::addFilter('fetch_emails.should_save_thread', function($save_thread, $data) {
            // Process only replies to notificaitons.
            if (!empty($data['message_from_customer'])) {
                return $save_thread;
            }

            // Check if text starts with command
            preg_match("/^[\s]*(@".implode('|@', self::getCommands()).")/su", trim(strip_tags($data['body'])), $m);
            
            if (empty($m[1])) {
                return $save_thread;
            }

            // Get first occurence of command.
            preg_match("/(@".implode('|@', self::getCommands()).")([\s<]+.*)/su", $data['body'], $m);
            
            if (empty($m[1])) {
                return $save_thread;
            }

            $command = $m[1];

            // Cut command from text.
            $text = preg_replace("/".$command."([\s<]+)/su", '$1', $data['body']);
            $text = trim($text);

            switch ($command) {
                case '@note':
                    $this->commandNote($text, $data);
                    $save_thread = false;
                    break;
                case '@assign':
                case '@me':
                    $this->commandAssign($text, $data, $command);
                    $save_thread = false;
                    break;
                case '@status':
                case '@active':
                case '@pending':
                case '@closed':
                case '@spam':
                    $this->commandStatus($text, $data, $command);
                    $save_thread = false;
                    break;
                case '@tag':
                    $this->commandTag($text, $data, $command);
                    $save_thread = false;
                    break;
                default:
                    $save_thread = \Eventy::filter('emailcommands.process_command', $save_thread, $command, $text, $data);
                    break;
            }

            return $save_thread;
        }, 20, 2);

        // Add link to the notification footer
        \Eventy::addAction('email_notification.footer_links', function($mailbox, $conversation, $threads) {
            echo ' - <a href="'.config('app.freescout_url').'/module/email-commands/" style="color:#B5B9BD;">'.__('Available email commands').'</a>';
        }, 20, 3);

        // Add link to the text notification footer
        \Eventy::addAction('email_notification_text.footer_links', function($mailbox, $conversation, $threads) {
            echo "--
".__('Reply with any of these commands to update the conversation:')."
".config('app.freescout_url')."/module/email-commands/";
        }, 20, 3);
    }

    /**
     * Get commands list.
     * 
     * @return [type] [description]
     */
    public static function getCommands()
    {
        return \Eventy::filter('emailcommands.commands', self::$commands);
    }

    /**
     * Create note command.
     * 
     * @param  [type] $data     [description]
     * @param  [type] $argument [description]
     * @return [type]           [description]
     */
    public function commandNote($text, $data)
    {
        $conversation = null;

        if ($data['message_from_customer'] || empty($data['user'])) {
            return false;
        }

        if (!empty($data['prev_thread'])) {
            $conversation = $data['prev_thread']->conversation;
        } else {
            return false;
        }
        $thread_data = [
            'user_id'       => $conversation->user_id,
            'created_by_user_id' => $data['user']->id,
            'source_via'    => Thread::PERSON_USER,
            'source_type'   => Thread::SOURCE_TYPE_EMAIL,
        ];

        $thread = Thread::create($conversation, Thread::TYPE_NOTE, $text, $thread_data);

        if ($thread) {
            event(new UserAddedNote($conversation, $thread));
            \Eventy::action('conversation.note_added', $conversation, $thread);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Assign ticket command.
     * 
     * @param  [type] $data     [description]
     * @param  [type] $argument [description]
     * @return [type]           [description]
     */
    public function commandAssign($text, $data, $command)
    {
        $conversation = null;
        $assignees = [];
        $new_user_id = null;

        if ($data['message_from_customer'] || empty($data['user'])) {
            return false;
        }

        if (!empty($data['prev_thread'])) {
            $conversation = $data['prev_thread']->conversation;
        } else {
            return false;
        }

        $user = $data['user'];

        if ($command == '@me') {
            $new_user = $data['user'];
        } else {
            // Prepare text
            $text = (new \Html2Text\Html2Text($text))->getText();

            // Get first line of the text
            $text = \Helper::trim($text);
            $text = strtok($text, "\n");
            //$text = strip_tags($text);
            $text = mb_strtolower($text);
            $text = \Helper::trim($text);

            // Fetch command params
            $parts = preg_split("/\s+/u", $text);
            $first_name = '';
            $last_name = '';
            if (isset($parts[0])) {
                $first_name = $parts[0];
            }
            if (isset($parts[1])) {
                $last_name = $parts[1];
            }

            // Find user
            if ($first_name && $last_name && !strstr($first_name, '@')) {
                // First and last name
                $assignees = User::where([
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                    ])->get();
            } elseif (strstr($first_name, '@')) {
                // Email
                $new_user_by_email = User::where([
                        'email' => $first_name,
                    ])->first();
                if ($new_user_by_email) {
                    $assignees[] = $new_user_by_email;
                }
            } else {
                // Name
                // Search user by name in the mailbox
                $assignees = User::where([
                        'first_name' => $first_name,
                    ])->get();
            }

            // Check if user has access to the mailbox
            if (count($assignees)) {
                foreach ($assignees as $assignee) {
                    if ($assignee->hasAccessToMailbox($conversation->mailbox_id)) {
                        $new_user = $assignee;
                        break;
                    }
                }
            }
        }

        if (!$new_user) {
            return false;
        }

        $conversation->setUser($new_user->id);
        $conversation->save();

        // Create lineitem thread
        $thread_data = [
            'user_id'       => $new_user->id,
            'created_by_user_id' => $user->id,
            'status'        => Thread::STATUS_NOCHANGE,
            'action_type'   => Thread::ACTION_TYPE_USER_CHANGED,
            'customer_id'   => $conversation->customer_id,
            'source_via'    => Thread::PERSON_USER,
            'source_type'   => Thread::SOURCE_TYPE_EMAIL,
        ];

        $thread = Thread::create($conversation, Thread::TYPE_LINEITEM, '', $thread_data);

        if ($thread) {
            event(new ConversationUserChanged($conversation, $user));
            \Eventy::action('conversation.user_changed', $conversation, $user);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Change ticket status command.
     * 
     * @param  [type] $data     [description]
     * @param  [type] $argument [description]
     * @return [type]           [description]
     */
    public function commandStatus($text, $data, $command)
    {
        $conversation = null;
        $new_status = null;

        if ($data['message_from_customer'] || empty($data['user'])) {
            return false;
        }

        if (!empty($data['prev_thread'])) {
            $conversation = $data['prev_thread']->conversation;
        } else {
            return false;
        }

        $user = $data['user'];

        switch ($command) {
            case '@active':
                $new_status = Thread::STATUS_ACTIVE;
                break;
            
            case '@pending':
                $new_status = Thread::STATUS_PENDING;
                break;
                
            case '@closed':
                $new_status = Thread::STATUS_CLOSED;
                break;       
                         
            case '@spam':
                $new_status = Thread::STATUS_SPAM;
                break;

            default:

                // Prepare text
                $text = (new \Html2Text\Html2Text($text))->getText();

                // Get first line of the text
                $text = \Helper::trim($text);
                $text = strtok($text, "\n");
                //$text = strip_tags($text);
                $text = mb_strtolower($text);
                $text = \Helper::trim($text);

                // Fetch command params
                $parts = preg_split("/\s+/u", $text);

                if (isset($parts[0])) {
                    $new_status = array_search($parts[0], Thread::$statuses);
                }

                break;
        }

        if (!$new_status) {
            return false;
        }

        $prev_status = $conversation->status;
        $conversation->setStatus($new_status, $user);
        $conversation->save();

        // Create lineitem thread
        $thread_data = [
            'user_id'       => $conversation->user_id,
            'created_by_user_id' => $user->id,
            'status'        => $new_status,
            'action_type'   => Thread::ACTION_TYPE_STATUS_CHANGED,
            'customer_id'   => $conversation->customer_id,
            'source_via'    => Thread::PERSON_USER,
            'source_type'   => Thread::SOURCE_TYPE_EMAIL,
        ];

        $thread = Thread::create($conversation, Thread::TYPE_LINEITEM, '', $thread_data);

        if ($thread) {
            event(new ConversationStatusChanged($conversation));
            \Eventy::action('conversation.status_changed', $conversation, $user, false, $prev_status);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Add tags to ticket.
     * 
     * @param  [type] $data     [description]
     * @param  [type] $argument [description]
     * @return [type]           [description]
     */
    public function commandTag($text, $data, $command)
    {
        $conversation = null;
        $tags = [];

        // Check if module is active
        if (!\Module::isActive('tags')) {
            return false;
        }

        if ($data['message_from_customer'] || empty($data['user'])) {
            return false;
        }

        if (!empty($data['prev_thread'])) {
            $conversation = $data['prev_thread']->conversation;
        } else {
            return false;
        }

        $user = $data['user'];

        // Prepare text
        $text = (new \Html2Text\Html2Text($text))->getText();

        // Get first line of the text
        $text = \Helper::trim($text);
        $text = strtok($text, "\n");
        //$text = strip_tags($text);
        $text = mb_strtolower($text);
        $text = \Helper::trim($text);

        // Fetch command params
        $tags = explode(',', $text);

        if (!$tags) {
            return false;
        }

        foreach ($tags as $tag_name) {
            //$tag_name = \Helper::entities2utf8($tag_name);
            \Modules\Tags\Entities\Tag::add($tag_name, $conversation->id);
        }

        return false;
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
            __DIR__.'/../Config/config.php' => config_path('emailcommands.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'emailcommands'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/emailcommands');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/emailcommands';
        }, \Config::get('view.paths')), [$sourcePath]), 'emailcommands');
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
