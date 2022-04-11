<?php

namespace Modules\SatRatings\Providers;

use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('SATR_MODULE', 'satratings');

class SatRatingsServiceProvider extends ServiceProvider
{
    /**
     * Ratings Playcement: place ratings text above/below signature.
     */
    const PLACEMENT_ABOVE = 1;
    const PLACEMENT_BELOW = 2;

    /**
     * Default rating text.
     */
    const DEFAULT_TEXT = '<br>
How would you rate my reply?
<br>
<a href="{%ratings.great%}" style="color:#50bc1c;">Great</a> &nbsp;&nbsp; <a href="{%ratings.okay%}" style="color:#555555;">Okay</a> &nbsp;&nbsp; <a href="{%ratings.bad%}" style="color:#f10000;">Not Good</a><br>';

    /**
     * Rating values.
     */
    const RATING_GREAT = 1;
    const RATING_OKAY  = 2;
    const RATING_BAD   = 3;

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
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }


    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Select main menu item.
        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['manage']['mailboxes'][] = 'mailboxes.sat_ratings';
            $menu['manage']['mailboxes'][] = 'mailboxes.sat_ratings_trans';

            return $menu;
        });

        // Add Sat. Ratings item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('satratings::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 30);

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($value) {
            //array_push($value, '/modules/'.SATR_MODULE.'/js/laroute.js');
            //array_push($value, '/modules/'.SATR_MODULE.'/js/vars.js');
            array_push($value, '/modules/'.SATR_MODULE.'/js/module.js');
            return $value;
        });

        // Add rating to the email to customer
        \Eventy::addAction('reply_email.before_signature', function($thread, $loop, $threads, $conversation, $mailbox) {
            
            if ($mailbox->ratings && $mailbox->ratings_placement == self::PLACEMENT_ABOVE &&
                $loop->first && $thread->source_via == Thread::PERSON_USER
            ) {
                echo '<br><br>'.$this->replacePlaceholders($mailbox->ratings_text ?? self::DEFAULT_TEXT, $thread, $mailbox);
            }
        }, 10, 5);

        // Add rating to the email to customer
        \Eventy::addAction('reply_email.after_signature', function($thread, $loop, $threads, $conversation, $mailbox) {
            if ($mailbox->ratings && $mailbox->ratings_placement == self::PLACEMENT_BELOW && 
                $loop->first && $thread->source_via == Thread::PERSON_USER
            ) {
                echo '<br>'.$this->replacePlaceholders($mailbox->ratings_text ?? self::DEFAULT_TEXT, $thread, $mailbox);
            }
        }, 10, 5);

        // Show rating in the conversation thread
        \Eventy::addAction('thread.after_person_action', function($thread, $loop, $threads, $conversation, $mailbox) {
            if (!empty($thread->rating)) {
                $text = '';
                switch ($thread->rating) {
                    case \SatRatingsHelper::RATING_GREAT:
                        $text = __('Great Rating');
                        $class = 'success';
                        break;
                    case \SatRatingsHelper::RATING_OKAY:
                        $text = __('Okay Rating');
                        $class = '';
                        break;
                    case \SatRatingsHelper::RATING_BAD:
                        $text = __('Not Good Rating');
                        $class = 'danger';
                        break;
                }
                $comment = '';
                if (!empty($thread->rating_comment)) {
                    $comment = $thread->rating_comment;
                }
                if ($text) {
                    if ($comment) {
                        echo '<a href="javascript:void(0)" data-toggle="popover" data-trigger="click" data-placement="bottom" data-content="'.htmlspecialchars($comment).'" title="'.__('Rating Comment').'">';
                    }
                    echo '<i class="badge '.$class.' margin-left-10" style="margin-top:-2px;">'.$text.($comment ? ' &nbsp;<span class="glyphicon glyphicon-comment"></span>' : '').'</i>';
                    if ($comment) {
                        echo '</a>';
                    }
                }
            }
        }, 10, 5);
    }

    /**
     * Replace placeholders in the rating text.
     */
    public function replacePlaceholders($text, $thread, $mailbox)
    {
        $params = [
            'thread_id' => $thread->id,
            'hash'      => base64_encode(\Hash::make($thread->id)),
        ];
        $replace = [
            '{%ratings.great%}' => route('sat_ratings.record', array_merge($params, ['rating' => self::RATING_GREAT])),
            '{%ratings.okay%}'  => route('sat_ratings.record', array_merge($params, ['rating' => self::RATING_OKAY])),
            '{%ratings.bad%}'  => route('sat_ratings.record', array_merge($params, ['rating' => self::RATING_BAD])),
        ];

        $text = strtr($text, $replace);

        // Replace vars
        $text = \MailHelper::replaceMailVars($text, [
            'mailbox' => $mailbox
        ]);

        return $text;
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('satratings.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'satratings'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/satratings');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/satratings';
        }, \Config::get('view.paths')), [$sourcePath]), 'satratings');
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

    /**
     * Get default translations.
     */
    public static function getDefaultTrans()
    {
        return [
            'title'           => __('Satisfaction Ratings'),
            'success_title'   => __('Thanks for your rating!'),
            'level_great'     => __('Great'),
            'level_okay'      => __('Okay'),
            'level_bad'       => __('Not Good'),
            'comment'         => __('Would you like to share any other comments?'),
            'comment_placeholder' => __('(optional)'),
            'submit'          => __('Send'),
            'success_message' => __('Feedback sent'),
        ];
    }

    public static function getTranslations($mailbox)
    {
        $trans = \Helper::jsonToArray($mailbox->ratings_trans);
        if (!$trans) {
            $trans = \SatRatingsHelper::getDefaultTrans();
        } else {
            // Make sure that array has all keys
            $trans = array_merge(\SatRatingsHelper::getDefaultTrans(), $trans);
        }

        return $trans;
    }
}
