<?php

namespace Modules\Slack\Providers;

use App\Mailbox;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Frlnc\Slack\Http\SlackResponseFactory;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Core\Commander;

require_once __DIR__.'/../vendor/autoload.php';

define('SLACK_MODULE', 'slack');

class SlackServiceProvider extends ServiceProvider
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
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's JS file to the application layout.
        // \Eventy::addFilter('javascripts', function($javascripts) {
        //     $javascripts[] = \Module::getPublicPath(SLACK_MODULE).'/js/laroute.js';
        //     $javascripts[] = \Module::getPublicPath(SLACK_MODULE).'/js/module.js';
        //     return $javascripts;
        // });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['slack'] = ['title' => __('Slack'), 'icon' => 'tasks', 'order' => 500];

            return $sections;
        }, 30);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != 'slack') {
                return $settings;
            }
           
            $settings = \Option::getOptions([
                'slack.api_token',
                'slack.events',
                'slack.channels_mapping',
                //'slack.active',
            ]);

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != 'slack') {
                return $params;
            }
            
            $channels_mapping = [];
            $prev_channels_mapping = \Option::get('slack.channels_mapping', []);

            $channels_error = false;
            $channels = [];
            $token_error = '';

            if (\Option::get('slack.api_token')) {
                // https://api.slack.com/methods/conversations.list
                $channels_response = self::apiCall('conversations.list', [
                    'limit' => 1000,
                    'types' => 'public_channel,private_channel,mpim'
                ]);

                // We are using channels also to check API Token.
                if (isset($channels_response['ok']) && $channels_response['ok']) {
                    \Option::set('slack.active', true);
                    $token_error = '';
                } elseif (isset($channels_response['ok'])) {
                    \Option::set('slack.active', false);
                    if (!empty($channels_response['error'])) {
                        $token_error = $channels_response['error'];
                    } else {
                        $token_error = 'unknown_error';
                    }
                }

                if (empty($channels_response['ok'])) {
                    $channels_error = true;
                    // Get remembered channels.
                    $channels = \Option::get('slack.channels');
                } elseif (!empty($channels_response['channels'])) {
                    foreach ($channels_response['channels'] as $ch) {
                        if (isset($ch['id']) && isset($ch['name'])) {
                            $channels[] = [
                                'id'   => $ch['id'],
                                'name' => $ch['name'],
                            ];
                        }
                    }
                    \Option::set('slack.channels', $channels);
                }
            } elseif (\Option::get('slack.active')) {
                \Option::set('slack.active', false);
            }
            $mailboxes = Mailbox::select(['id', 'name'])->get();

            foreach ($mailboxes as $mailbox) {
                $mapped = false;
                foreach ($prev_channels_mapping as $mailbox_id => $channel_mapping) {
                    if ($mailbox_id == $mailbox->id) {
                        $mapped = true;
                        break;
                    }
                }
                if (!$mapped) {
                    // Mailbox not mapped to channel yet.
                    $channel_mapping = null;
                }
                $channels_mapping[$mailbox->id] = [
                    'mailbox' => $mailbox,
                    'mapping' => $channel_mapping
                ];
            }

            $params = [
                'template_vars' => [
                    'events' => [
                        'conversation.created'        => __('Conversation Created'),
                        'conversation.assigned'       => __('Conversation Assigned'),
                        'conversation.note_added'     => __('Conversation Note Added'),
                        'conversation.customer_replied' => __('Conversation Customer Reply'),
                        'conversation.user_replied'    => __('Conversation Agent Reply'),
                        //'conversation.merged' => __('Conversation Merged'),
                        //'conversation.moved' => __('Conversation Moved'),
                        'conversation.status_changed'  => __('Conversation Status Updated'),
                    ],
                    'channels_mapping' => $channels_mapping,
                    'channels'         => $channels,
                    'channels_error'   => $channels_error,
                    'token_error'      => $token_error,
                    'active'           => \Option::get('slack.active'),
                ]
            ];

            // Allow to add extra events
            $params['template_vars']['events'] = \Eventy::filter('slack.events', $params['template_vars']['events']);

            return $params;
        }, 20, 2);


        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != 'slack') {
                return $view;
            } else {
                return 'slack::settings';
            }
        }, 20, 2);
        
        // Send slack notification.
        // https://api.slack.com/docs/message-formatting
        // https://api.slack.com/docs/message-attachments
        \Eventy::addAction('slack.post', function($conversation, $pretext, $fields = [], $color = '') {
            // Detect channel by mailbox.
            $channel = '';
            $mailbox_id = $conversation->mailbox_id;
            
            $channels_mapping = \Option::get('slack.channels_mapping');
            if (!$mailbox_id || empty($channels_mapping) || empty($channels_mapping[$mailbox_id])) {
                return false;
            } else {
                $channel = $channels_mapping[$mailbox_id];
            }

            // Count mailboxes.

            // Default fields.
            $default_fields = [
                'conversation' => [
                    'title' => $conversation->getSubject(),
                ],
                'customer' => [
                    'title' => __('Customer'),
                    'short' => true,
                ],
                'mailbox' => [
                    'title' => __('Mailbox'),
                    'short' => true,
                ],
            ];

            // Remove mailbox if there is only one active mailbox.
            $mailboxes = \App\Mailbox::getActiveMailboxes();
            if (count($mailboxes) == 1) {
                unset($default_fields['mailbox']);
            }

            if (!is_array($fields)) {
                $fields = [];
            }
            $fields = array_merge($default_fields, $fields);

            $formatted_fields = [];
            foreach ($fields as $name => $field) {
                if (!$field) {
                    continue;
                }
                if (empty($field['value'])) {
                    $value = '';
                    switch ($name) {
                        case 'conversation':
                            $value = $conversation->getLastReply(true)->body;
                            $value = \Helper::htmlToText($value);
                            $value = self::slackEscape($value);
                            break;
                        case 'customer':
                            $customer = $conversation->customer;
                            if ($customer) {
                                $email = $customer->getMainEmail();
                                $email_markup = '<mailto:'.$email.'|'.$email.'>';
                                if ($customer->getFullName()) {
                                    $value = $customer->getFullName().' '.$email_markup;
                                } else {
                                    $value = $email_markup;
                                }
                            }
                            break;
                        case 'mailbox':
                            $mailbox = $conversation->mailbox;
                            if ($mailbox) {
                                $value = $mailbox->name;
                            }
                            break;
                    }
                    $field['value'] = $value;
                    $fields[$name]['value'] = $value;
                }
                if ($name != 'conversation') {
                    $formatted_fields[] = $field;
                }
            }
            $pretext = self::slackEscape($pretext).' <'.$conversation->url().'|#'.$conversation->number.'>';

            if (empty($color)) {
                $color = config('app.colors')['main_light'];
            }

            // Conversation field becomes a text.
            $text = '';
            if ($fields['conversation']) {
                $text = "*".$fields['conversation']['title']."*\n";
                $text .= $fields['conversation']['value'];
                $text = self::slackEscape($text);
            }

            self::apiCall('chat.postMessage', [
                'channel' => $channel,
                'attachments' => json_encode([[
                    'pretext' => $pretext,
                    'text'    => $text,
                    'color'   => $color,
                    "mrkdwn_in" => ["pretext", "text"],
                    'fields'  => $formatted_fields
                ]])
            ]);
        }, 20, 4);

        // Listeners
        
        // Conversation Created.
        \Eventy::addAction('conversation.created_by_user', function($conversation, $thread) {
            if (!self::isEventEnabled('conversation.created')) {
                return false;
            }
            $user_name = '';
            if ($conversation->created_by_user) {
                $user_name = $conversation->created_by_user->getFullName();
            }
            \Helper::backgroundAction('slack.post', [
                $conversation,
                __('A *New Conversation* was created by :user_name', [
                    'user_name'   => $user_name,
                ]),
            ]);
        }, 20, 2);

        \Eventy::addAction('conversation.created_by_customer', function($conversation, $thread) {
            if (!self::isEventEnabled('conversation.created')) {
                return false;
            }
            \Helper::backgroundAction('slack.post', [
                $conversation,
                __('A *New Conversation* was created'),
            ]);
        }, 20, 2);

        // Conversation assigned
        \Eventy::addAction('conversation.user_changed', function($conversation, $by_user) {
            if (!self::isEventEnabled('conversation.assigned')) {
                return false;
            }
            $assignee_name = '';
            if ($conversation->user_id && $conversation->user) {
                $assignee_name = $conversation->user->getFullName();
            }
            \Helper::backgroundAction('slack.post', [
                $conversation,
                __('Conversation *assigned* to *:assignee_name* by :user_name', [
                    'assignee_name' => $assignee_name,
                    'user_name'     => $by_user->getFullName(),
                ]),
            ]);
        }, 20, 2);

        // Note added.
        \Eventy::addAction('conversation.note_added', function($conversation, $thread) {
            if (!self::isEventEnabled('conversation.note_added')) {
                return false;
            }
            $note_text = $thread->body;
            $note_text = \Helper::htmlToText($note_text);
            $note_text = self::slackEscape($note_text);

            $fields['conversation'] = [
                'title' => $conversation->getSubject(),
                'value' => $note_text,
            ];

            \Helper::backgroundAction('slack.post', [
                $conversation,
                __('A *note was added* by :user_name', [
                    'user_name'     => $thread->created_by_user->getFullName(),
                ]),
                $fields,
                config('app.colors')['note'],
            ]);
        }, 20, 2);

        // Conversation Customer Reply.
        \Eventy::addAction('conversation.customer_replied', function($conversation, $thread) {
            if (!self::isEventEnabled('conversation.customer_replied')) {
                return false;
            }
            \Helper::backgroundAction('slack.post', [
                $conversation,
                __('A customer *replied* to a conversation'),
            ]);
        }, 20, 2);

        // Conversation Agent Reply.
        \Eventy::addAction('conversation.user_replied', function($conversation, $thread) {
            if (!self::isEventEnabled('conversation.user_replied')) {
                return false;
            }
            $user_name = '';
            if ($thread->created_by_user) {
                $user_name = $thread->created_by_user->getFullName();
            }
            \Helper::backgroundAction('slack.post', [
                $conversation,
                __(':user_name *replied*', [
                    'user_name' => $user_name,
                ]),
            ]);
        }, 20, 2);

        // Conversation Status Updated
        \Eventy::addAction('conversation.status_changed', function($conversation, $user, $changed_on_reply) {
            if ($changed_on_reply || !self::isEventEnabled('conversation.status_changed')) {
                return false;
            }
            // Create a background job for posting a message.
            \Helper::backgroundAction('slack.post', [
                $conversation,
                __('Conversation *status changed* to *:status* by :user_name', [
                    'status'    => $conversation->getStatusName(),
                    'user_name' => $user->getFullName(),
                ]),
            ]);
        }, 20, 3);
    }

    public static function isEventEnabled($event)
    {
        $events = \Option::get('slack.events');
        if (empty($events) || !is_array($events) || !in_array($event, $events)) {
            return false;
        } else {
            return true;
        }
    }

    public static function slackEscape($text)
    {
        return strtr($text, [
            '&' => '&amp;',
            '<' => '&lt;',
            '>' => '&gt;',
        ]);
    }

    public static function apiCall($method, $params = [])
    {
        $token = \Option::get('slack.api_token');

        if (!$token) {
            return ['ok' => false, 'error' => 'not_authed'];
        }

        $interactor = new CurlInteractor;
        $interactor->setResponseFactory(new SlackResponseFactory);

        $commander = new Commander($token, $interactor);

        $response = $commander->execute($method, $params);
        $body = $response->getBody();
        //print_r($response);
        if (empty($body['ok'])) {
            \Helper::log('slack', 'API error: '.json_encode($body).'; Method: '.$method.'; Parameters: '.json_encode($params));
        }
        return $body;
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
            __DIR__.'/../Config/config.php' => config_path('slack.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'slack'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/slack');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/slack';
        }, \Config::get('view.paths')), [$sourcePath]), 'slack');
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
