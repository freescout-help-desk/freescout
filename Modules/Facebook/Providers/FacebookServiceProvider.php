<?php

namespace Modules\Facebook\Providers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

require_once __DIR__.'/../vendor/autoload.php';

class FacebookServiceProvider extends ServiceProvider
{
    const DRIVER = 'facebook';

    const CHANNEL = 10;
    const CHANNEL_NAME = 'Facebook';

    const LOG_NAME = 'facebook_errors';
    const SALT = '1dwVMOD0RMl';

    public static $skip_messages = [
        '%%%_IMAGE_%%%',
        '%%%_VIDEO_%%%',
        '%%%_FILE_%%%',
        '%%%_AUDIO_%%%',
        '%%%_LOCATION_%%%',
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
        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('facebook::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 36);

        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['facebook'] = [
                'mailboxes.facebook.settings',
            ];
            return $menu;
        });

        \Eventy::addFilter('channel.name', function($name, $channel) {
            if ($name) {
                return $name;
            }
            if ($channel == self::CHANNEL) {
                return self::CHANNEL_NAME;
            } else {
                return $name;
            }
        }, 20, 2);

        \Eventy::addAction('chat_conversation.send_reply', function($conversation, $replies, $customer) {

            if ($conversation->channel != self::CHANNEL) {
                return;
            }

            if (!$customer->channel_id) {
                \Facebook::log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.', $conversation->mailbox);
                return;
            }
            $driver_class = self::getDriverClass();

            $botman = \Facebook::getBotman($conversation->mailbox, null, false);

            if (!$botman) {
                return;
            }
            
            // We send only the last reply.
            $replies = $replies->sortByDesc(function ($item, $key) {
                return $item->id;
            });
            $thread = $replies[0];

            // If thread is draft, it means it has been undone
            $thread = $thread->fresh();
            
            if ($thread->isDraft()) {
                return;
            }

            //$botman->typesAndWaits(2);
            
            $text = $thread->getBodyAsText();

            // Attachments.
            // Some drivers can not send message with attachment https://github.com/botman/driver-facebook/issues/65
            if ($thread->has_attachments) {
                foreach ($thread->attachments as $attachment) {
                    
                    $botman_attachment = null;

                    switch ($attachment->type) {
                        case Attachment::TYPE_IMAGE:
                            $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Image($attachment->url(), [
                                'custom_payload' => true,
                            ]);
                            break;
                        case Attachment::TYPE_VIDEO:
                            $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Video($attachment->url(), [
                                'custom_payload' => true,
                            ]);
                            break;
                        case Attachment::TYPE_AUDIO:
                            $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Audio($attachment->url(), [
                                'custom_payload' => true,
                            ]);
                            break;
                        default:
                            $attachment_url = $attachment->url();
                            $text .= "\n\n[".\Helper::remoteFileName($attachment_url)."] \n".$attachment_url."";
                    }

                    if ($botman_attachment) {
                        $message = \BotMan\BotMan\Messages\Outgoing\OutgoingMessage::create('')->withAttachment($botman_attachment);
                        $botman->say($message, $customer->channel_id, $driver_class);
                    }
                }
            }

            $botman->say($text, $customer->channel_id, $driver_class);

        }, 20, 3);
    }

    public static function getDriverClass()
    {
        return \BotMan\Drivers\Facebook\FacebookDriver::class;
    }

    public static function getBotman($mailbox, $request = null, $is_webhook = true)
    {
        $driver_config = $mailbox->meta['facebook'] ?? [];

        $driver_config['verification'] = \Facebook::getMailboxVerifyToken($mailbox->id);

        // Just to log.
        if ($request && $request->get('hub_mode') === 'subscribe' && $request->get('hub_verify_token') !== $driver_config['verification']) {
            \Facebook::log('Incorrect Facebook Verify Token: '.$request->get('hub_verify_token'), $mailbox, $is_webhook);
        }
        if (empty($driver_config['token']) || empty($driver_config['app_secret'])) {
            \Facebook::log('Webhook executed, but '.self::CHANNEL_NAME.' is not configured for this mailbox.', $mailbox, $is_webhook);
            return false;
        }
        
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookAudioDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookFileDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookImageDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookLocationDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookLocationDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookVideoDriver::class);

        return \BotMan\BotMan\BotManFactory::create([
            self::DRIVER => $driver_config
        ], new \BotMan\BotMan\Cache\LaravelCache());
    }

    public static function processIncomingMessage($bot, $text, $mailbox, $files = [])
    {
        if ($bot->isBot()) {
            return false;
        }

        if (in_array($text, self::$skip_messages) && empty($files)) {
            return false;
        }

        $messenger_user = null;
        $customer_info = [];
        try {            
            // Due to European regulations, sometimes user can not be retrieved.
            // https://github.com/freescout-helpdesk/freescout/issues/1106
            $messenger_user = $bot->getUser();
            $customer_info['id'] = $messenger_user->getId();
            $customer_info['first_name'] = $messenger_user->getFirstName();
            $customer_info['last_name'] = $messenger_user->getLastName();
        } catch (\Exception $e) {
            $customer_info['id'] = $bot->getMessage()->getSender();
            $customer_info['first_name'] = 'Facebook';
            $customer_info['last_name'] = substr(crc32(time()), 0, 5);
            $customer_info['email'] = '';
            $customer_info['profile_pic'] = '';
        }
        
        if (!$customer_info) {
            \Facebook::log('Could not get user info.', $mailbox);
            return;
        }

        // Get or creaate a customer.
        $channel_id = $customer_info['id'];
        if (!$channel_id) {
            \Facebook::log('User has no ID: '.($messenger_user ? json_encode($messenger_user->getInfo()) : '').'. Check App Logs', $mailbox);
            return;
        }
        $channel = \Facebook::CHANNEL;

        $customer = Customer::where('channel', $channel)
            ->where('channel_id', $channel_id)
            ->first();

        if (!$customer) {
            if ($messenger_user) {
                $customer_info = $messenger_user->getInfo();
            }
            $customer_data = [
                'channel' => $channel,
                'channel_id' => $channel_id,
                'first_name' => $customer_info['first_name'] ?: $channel_id,
                'last_name' => $customer_info['last_name'],
                // 'social_profiles' => Customer::formatSocialProfiles([[
                //     'type' => Customer::SOCIAL_TYPE_FACEBOOK,
                // ]])
            ];

            // Social networks.
            $email = $customer_info['email'] ?? $customer_info['mail'] ?? '';
            if ($email) {
                $customer = Customer::create($email, $customer_data);
            } else {
                $customer = Customer::createWithoutEmail($customer_data);
            }
            if (!$customer) {
                \Facebook::log('Could not create a customer.', $mailbox);
                return;
            }
            // Set photo.
            $photo_url = $customer_info['profile_pic'] ?? '';
            if ($photo_url) {
                if ($customer->setPhotoFromRemoteFile($photo_url)) {
                    $customer->save();
                }
            }
        }
        //$bot->reply('Customer ID:'.$customer->id);

        // Get last customer conversation or create a new one.
        $conversation = Conversation::where('mailbox_id', $mailbox->id)
            ->where('customer_id', $customer->id)
            ->where('channel', $channel)
            ->orderBy('created_at', 'desc')
            ->first();

        $attachments = [];

        if (count($files)) {
            foreach ($files as $file) {
                $file_url = $file->getUrl();
                if (!$file_url) {
                    continue;
                }
                $attachments[] = [
                    'file_name' => \Helper::remoteFileName($file_url),
                    'file_url' => $file_url,
                ];
            }
        }

        if ($conversation) {
            // Create thread in existing conversation.
            Thread::createExtended([
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ],
                $conversation,
                $customer
            );
        } else {
            // Create conversation.
            Conversation::create([
                    'type' => Conversation::TYPE_CHAT,
                    'subject' => Conversation::subjectFromText($text),
                    'mailbox_id' => $mailbox->id,
                    'source_type' => Conversation::SOURCE_TYPE_WEB,
                    'channel' => $channel,
                ], [[
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ]],
                $customer
            );
        }
    }

    public static function getMailboxSecret($id)
    {
        return crc32(config('app.key').$id.'salt'.self::SALT);
    }

    public static function getMailboxVerifyToken($id)
    {
        return crc32(config('app.key').$id.'verify'.self::SALT).'';
    }

    public static function log($text, $mailbox = null, $is_webhook = true)
    {
        \Helper::log(\Facebook::LOG_NAME, '['.self::CHANNEL_NAME.($is_webhook ? ' Webhook' : '').'] '.($mailbox ? '('.$mailbox->name.') ' : '').$text);
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
            __DIR__.'/../Config/config.php' => config_path('facebook.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'facebook'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/facebook');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/facebook';
        }, \Config::get('view.paths')), [$sourcePath]), 'facebook');
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
