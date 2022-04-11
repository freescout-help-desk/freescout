<?php

namespace Modules\SpamFilter\Providers;

// It has to be included here to require vendor service providers in module.json
require_once __DIR__.'/../vendor/autoload.php';

use App\Conversation;
use App\Folder;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Storage;
use PHPAntiSpam\Corpus\ArrayCorpus;
use PHPAntiSpam\Classifier;
use PHPAntiSpam\Tokenizer\WhitespaceTokenizer;

// Module alias
define('SPAM_FILTER_MODULE', 'spamfilter');

class SpamFilterServiceProvider extends ServiceProvider
{
    // Customer spam statuses.
    const SPAM_STATUS_EMAIL_BLACKLISTED_HARD = 30; // Email blacklisted manually by user.
    const SPAM_STATUS_EMAIL_BLACKLISTED_SOFT = 20; // Email blacklisted by Spam button.
    const SPAM_STATUS_DOMAIN_BLACKLISTED = 10;
    const SPAM_STATUS_DEFAULT = 0;
    const SPAM_STATUS_DOMAIN_WHITELISTED = -10;
    const SPAM_STATUS_EMAIL_WHITELISTED_SOFT = -20; // Email whitelisted by Not Spam button.
    const SPAM_STATUS_EMAIL_WHITELISTED_HARD = -30; // Email whitelisted manually by user.

    // Action type.
    const ACTION_TYPE_FILTERED_AS_SPAM = 101;

    // Categories for statistical analysis.
    const STATISTICAL_CATEGORY_SPAM = 'spam';
    const STATISTICAL_CATEGORY_NOSPAM = 'nospam';

    const STATISTICAL_DB_FOLDER = 'spamfilter';

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
            $styles[] = \Module::getPublicPath(SPAM_FILTER_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(SPAM_FILTER_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(SPAM_FILTER_MODULE).'/js/module.js';
            return $javascripts;
        });
        
        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections[SPAM_FILTER_MODULE] = ['title' => __('Spam Filter'), 'icon' => 'ban-circle', 'order' => 350];

            return $sections;
        }, 18);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != SPAM_FILTER_MODULE) {
                return $settings;
            }
           
            $settings['spamfilter.auto'] = config('spamfilter.auto');

            // $settings = \Option::getOptions([
            //     'spamfilter.auto',
            // ], self::$default_options);

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != SPAM_FILTER_MODULE) {
                return $params;
            }

            $mailboxes = Mailbox::orderBy('name')->get()->toArray();

            foreach ($mailboxes as $i => $mailbox) {
                $mailboxes[$i]['size'] = \Helper::humanFileSize(self::getStatisticalDbSize($mailbox['id']));
            }

            $params = [
                'template_vars' => [
                    'mailboxes' => $mailboxes,
                ],
                'settings' => [
                    'spamfilter.auto' => [
                        'env' => 'SPAMFILTER_AUTO',
                    ],
                ]
            ];

            return $params;
        }, 20, 2);


        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != SPAM_FILTER_MODULE) {
                return $view;
            } else {
                return 'spamfilter::settings';
            }
        }, 20, 2);

        // Conversation marked as spam
        \Eventy::addAction('conversation.status_changed', function($conversation, $user, $changed_on_reply, $prev_status) {
            
            // Check only conversations created by customers.
            if ($conversation->source_via != Conversation::PERSON_CUSTOMER) {
                return;
            }

            $spam_status = self::getCustomerSpamStatus($conversation->customer->spam_status, $conversation->mailbox_id);

            // Do not change status if customer has been whielisted/blacklisted manually.
            if (in_array($spam_status, [self::SPAM_STATUS_EMAIL_WHITELISTED_HARD, self::SPAM_STATUS_EMAIL_BLACKLISTED_HARD])) {
                return;
            }
            if ($conversation->status == Conversation::STATUS_SPAM) {
                $thread = $conversation->getFirstThread();
                if ($thread && !$thread->isAutoResponder()) {
                    // Mark customer as spammer
                    self::setCustomerSpamStatus($conversation->customer, $conversation->mailbox_id, self::SPAM_STATUS_EMAIL_BLACKLISTED_SOFT, $user->id, ['conversation_id' => $conversation->id]);
                    $conversation->customer->save();

                    // Update statistical analysis DB.
                    self::addToStatisticalDb($conversation->subject.' '.$thread->body, self::STATISTICAL_CATEGORY_SPAM, $conversation->mailbox_id);
                }
            } elseif ($prev_status == Conversation::STATUS_SPAM) {
                // Remove spammer status from customer
                self::setCustomerSpamStatus($conversation->customer, $conversation->mailbox_id, self::SPAM_STATUS_EMAIL_WHITELISTED_SOFT, $user->id, ['conversation_id' => $conversation->id]);
                $conversation->customer->save();

                // Update statistical analysis DB.
                $thread = $conversation->getFirstThread();
                if (!$thread->isAutoResponder()) {
                    // Update statistical analysis DB.
                    self::addToStatisticalDb($conversation->subject.' '.$thread->body, self::STATISTICAL_CATEGORY_NOSPAM, $conversation->mailbox_id);
                }
            }

        }, 20, 4);

        \Eventy::addAction('customer.profile_data', function($customer, $conversation) {
            if (!$conversation) {
                return false;
            }
            $spam_status = self::getCustomerSpamStatus($customer->spam_status, $conversation->mailbox_id);
            if ($spam_status && !in_array($spam_status['spam_status'], [self::SPAM_STATUS_EMAIL_WHITELISTED_SOFT, self::SPAM_STATUS_DEFAULT])) {
                $blacklisted = in_array($spam_status['spam_status'], array(self::SPAM_STATUS_EMAIL_BLACKLISTED_SOFT, self::SPAM_STATUS_EMAIL_BLACKLISTED_HARD));
                ?>
                    <div class="customer-section">
                        <div class="spam-status">
                            <span class="label label-<?php if ($blacklisted): ?>danger<?php else: ?>success<?php endif ?>"><?php if ($blacklisted): ?><?php echo __('Blacklisted') ?><?php else: ?><?php echo __('Whitelisted') ?><?php endif ?> (<?php echo __('Spam Filter') ?>)</span>
                            
                                <?php if (!empty($spam_status['conversation_id'])): ?>
                                    <a href="<?php echo Conversation::conversationUrl($spam_status['conversation_id']) ?>" class="sf-help" data-toggle="popover" data-trigger="hover" data-placement="left" data-content="<?php echo \App\User::find($spam_status['user_id'])->getFullName(); ?> (<?php echo User::dateFormat($spam_status['date'] ?? '') ?>)">
                                        <i class="glyphicon glyphicon-info-sign"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="sf-help" data-toggle="popover" data-trigger="hover" data-placement="left" data-content="<?php echo \App\User::find($spam_status['user_id'])->getFullName(); ?> (<?php echo User::dateFormat($spam_status['date'] ?? '') ?>)">
                                        <i class="glyphicon glyphicon-info-sign"></i>
                                    </span>
                                <?php endif ?>
                        </div>
                    </div>
                <?php
            }
        }, 20, 2);

        \Eventy::addAction('customer_profile.menu', function($customer, $conversation) {
            $spam_status = self::getCustomerSpamStatus($customer->spam_status, $conversation->mailbox_id);
            ?>
                <li class="divider"></li>
                <?php if (!in_array($spam_status['spam_status'], array(self::SPAM_STATUS_EMAIL_BLACKLISTED_SOFT, self::SPAM_STATUS_EMAIL_BLACKLISTED_HARD))): ?>
                    <li role="presentation"><a href="<?php echo route('customers.spam_filter.action', ['id' => $customer->id, 'action' => 'blacklist', 'conversation_id' => $conversation->id]) ?>" tabindex="-1" role="menuitem"><?php echo __("Blacklist Customer") ?> <small>(<?php echo __('Spam Filter') ?>)</small></a></li>
                <?php endif ?>
                <?php if (in_array($spam_status['spam_status'], array(self::SPAM_STATUS_EMAIL_BLACKLISTED_SOFT, self::SPAM_STATUS_EMAIL_BLACKLISTED_HARD))): ?>
                    <li role="presentation"><a href="<?php echo route('customers.spam_filter.action', ['id' => $customer->id, 'action' => 'unblacklist', 'conversation_id' => $conversation->id]) ?>" tabindex="-1" role="menuitem"><?php echo __("UnBlacklist") ?> <small>(<?php echo __('Spam Filter') ?>)</small></a></li>
                <?php endif ?>
                <?php if (!in_array($spam_status['spam_status'], array(self::SPAM_STATUS_EMAIL_WHITELISTED_HARD))): ?>
                    <li role="presentation"><a href="<?php echo route('customers.spam_filter.action', ['id' => $customer->id, 'action' => 'whitelist', 'conversation_id' => $conversation->id]) ?>" tabindex="-1" role="menuitem"><?php echo __("Whitelist Customer") ?> <small>(<?php echo __('Spam Filter') ?>)</small></a></li>
                <?php endif ?>
                <?php if (in_array($spam_status['spam_status'], array(self::SPAM_STATUS_EMAIL_WHITELISTED_HARD))): ?>
                    <li role="presentation"><a href="<?php echo route('customers.spam_filter.action', ['id' => $customer->id, 'action' => 'unwhitelist', 'conversation_id' => $conversation->id]) ?>" tabindex="-1" role="menuitem"><?php echo __("UnWhitelist") ?> <small>(<?php echo __('Spam Filter') ?>)</small></a></li>
                <?php endif ?>
            <?php
        }, 20, 2);

        // Process incoming emails.
        \Eventy::addFilter('conversation.created_by_customer', function($conversation, $thread, $customer) {

            $is_spam = false;

            // Check customer spammer status.
            $spam_status = self::getCustomerSpamStatus($conversation->customer->spam_status, $conversation->mailbox_id);

            switch ($spam_status['spam_status']) {
                case self::SPAM_STATUS_EMAIL_BLACKLISTED_SOFT:
                case self::SPAM_STATUS_EMAIL_BLACKLISTED_HARD:
                    $is_spam = true;
                    break;
            }

            // Use statistical analysis to detect spam.
            if (!$is_spam && config('spamfilter.auto')) {
                // Do not mark autoresponders as spam.
                if (!$thread->isAutoResponder()) {
                    // Do not check messages from whitelisted customers.
                    $spam_status = self::getCustomerSpamStatus($customer->spam_status, $conversation->mailbox_id);
                    if ($spam_status && !in_array($spam_status['spam_status'], [self::SPAM_STATUS_EMAIL_WHITELISTED_SOFT, self::SPAM_STATUS_EMAIL_WHITELISTED_HARD])) 
                    {
                        $is_spam = self::isSpamStatistical($conversation->subject.' '.$thread->body, $conversation->mailbox_id);
                    }
                }
            }

            // Mark as spam.
            if ($is_spam) {
                $spam_folder = $conversation->mailbox->getFolderByType(Folder::TYPE_SPAM);
                    
                if ($spam_folder) {

                    // Move conversation to Spam folder.
                    $conversation->folder_id = $spam_folder->id;
                    $conversation->status = Conversation::STATUS_SPAM;

                    // Create line item thread.
                    Thread::create($conversation, Thread::TYPE_LINEITEM, '', [
                        'user_id'     => $conversation->user_id,
                        'type'        => Thread::TYPE_LINEITEM,
                        'state'       => Thread::STATE_PUBLISHED,
                        'action_type' => self::ACTION_TYPE_FILTERED_AS_SPAM,
                        'source_via'  => Thread::PERSON_CUSTOMER,
                        'source_type' => Thread::SOURCE_TYPE_WEB,
                        'customer_id' => $conversation->customer_id,
                    ]);
                }
            }

            return $conversation;
        }, 20, 3);

        // Show action description for the line item thread.
        \Eventy::addFilter('thread.action_text', function($did_this, $thread, $conversation_number, $escape) {
            if ($thread->action_type == self::ACTION_TYPE_FILTERED_AS_SPAM) {
                if ($conversation_number) {
                    $did_this = __(':person marked as :status_name conversation #:conversation_number', ['status_name' => $thread->getStatusName(), 'conversation_number' => $conversation_number]);
                } else {
                    $did_this = __(':person marked as :status_name', ['status_name' => $thread->getStatusName(), 'conversation_number' => $conversation_number]);
                }
            }
            return $did_this;
        }, 20, 4);

        // Line item thread's person.
        \Eventy::addFilter('thread.action_person', function($person, $thread, $conversation_number) {
            if ($thread->action_type == self::ACTION_TYPE_FILTERED_AS_SPAM) {
                $person = __('Spam Filter');
            }
            return $person;
        }, 20, 3);
    }

    /**
     * Set customer spam status json value.
     * 
     * @param [type] $customer    [description]
     * @param [type] $mailbox_id  [description]
     * @param [type] $spam_status [description]
     * @param [type] $user_id     [description]
     */
    public static function setCustomerSpamStatus($customer, $mailbox_id, $spam_status, $user_id, $data = [])
    {
        $json_array = self::getCustomerSpamStatus($customer->spam_status);
        $data['spam_status'] = $spam_status;
        $data['user_id']     = $user_id;
        $data['date']        = date('Y-m-d H:i:s');

        $json_array[$mailbox_id]   = $data;

        $customer->spam_status = json_encode($json_array);
    }

    /**
     * Conver customer spam status into array.
     * 
     * @param  [type] $json_str [description]
     * @return [type]           [description]
     */
    public static function getCustomerSpamStatus($json_str, $mailbox_id = null)
    {
        $result = [];

        $data = \Helper::jsonToArray($json_str);
        if ($mailbox_id) {
            if (empty($data[$mailbox_id])) {
                $result = ['spam_status' => self::SPAM_STATUS_DEFAULT];
            } else {
                $result = $data[$mailbox_id];
            }
        } else {
            $result = $data;
        }

        return $result;
    }

    /**
     * Check if test is spam
     */
    public static function isSpamStatistical($text, $mailbox_id)
    {
        $text = self::normalizeStatisticalText($text);

        // Let's decleare our example training set
        $messages = self::getStatisticalDb($mailbox_id);

        // As tokenizer we can use the simplest one - WhitespaceTokenizer (but of course you can also use RegexpTokenizer
        // or create new one).
        $tokenizer = new WhitespaceTokenizer();

        // Let's define our corpus - collection of text documents.
        $corpus = new ArrayCorpus($messages, $tokenizer);

        // Run method
        $classifier = new Classifier($corpus);
        // http://www.paulgraham.com/sofar.html
        // http://sylpheed.sraoss.jp/sylfilter/
        //$classifier->setMethod(new \PHPAntiSpam\Method\BurtonMethod($corpus));
        $classifier->setMethod(new \PHPAntiSpam\Method\FisherRobinsonInverseChiSquareMethod($corpus));

        $spam_probability = $classifier->isSpam($text);

        if ($spam_probability['combined'] <= 0.55) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Add text to statistical analysis DB.
     */
    public static function addToStatisticalDb($text, $category, $mailbox_id)
    {
        // Normalize text.
        $data = self::getStatisticalDb($mailbox_id);

        $text = self::normalizeStatisticalText($text);

        // Check if text already in the DB.
        foreach ($data as $i => $item) {
            if ($item['content'] == $text) {
                unset($data[$i]);
            }
        }

        // Todo: limit number of saved text for spam and nospam categories.
        $data[] = [
            'category' => $category,
            'content' => $text
        ];

        self::setStatisticalDb(json_encode($data), $mailbox_id);
    }

    public static function setStatisticalDb($text, $mailbox_id)
    {
        $filepath = self::STATISTICAL_DB_FOLDER.DIRECTORY_SEPARATOR.$mailbox_id.'.json';
        try {
            Storage::disk('private')->put($filepath, $text);
        } catch(\Exception $e) {
            \Helper::logException($e);
        }
    }

    /**
     * Get texts from statistical analysis DB.
     */
    public static function getStatisticalDb($mailbox_id)
    {
        $filepath = self::STATISTICAL_DB_FOLDER.DIRECTORY_SEPARATOR.$mailbox_id.'.json';

        if (!Storage::disk('private')->exists($filepath)) {
            return [];
        }

        try {
            $filedata = Storage::disk('private')->get($filepath);
        } catch(\Exception $e) {
            \Helper::logException($e);
        }
        return \Helper::jsonToArray($filedata);
    }

    public static function getStatisticalDbSize($mailbox_id)
    {
        $filepath = self::STATISTICAL_DB_FOLDER.DIRECTORY_SEPARATOR.$mailbox_id.'.json';

        if (!Storage::disk('private')->exists($filepath)) {
            return 0;
        }

        try {
            return (int)Storage::disk('private')->size($filepath);
        } catch(\Exception $e) {
            \Helper::logException($e);
        }
        return 0;
    }

    /**
     * Strip tags from text and remove double spaces.
     */
    public static function normalizeStatisticalText($text)
    {
        $text = \Helper::htmlToText($text);
        $text = str_replace("\n", ' ', $text);
        // Run strip tags to remove double spaces, etc.
        $text = \Helper::stripTags($text);
        return $text;
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
            __DIR__.'/../Config/config.php' => config_path('spamfilter.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'spamfilter'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/spamfilter');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/spamfilter';
        }, \Config::get('view.paths')), [$sourcePath]), 'spamfilter');
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
