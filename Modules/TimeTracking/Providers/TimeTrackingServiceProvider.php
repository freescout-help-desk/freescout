<?php

namespace Modules\TimeTracking\Providers;

use App\Conversation;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\TimeTracking\Entities\Timelog;

// Module alias
define('TIMETR_MODULE', 'timetracking');

class TimeTrackingServiceProvider extends ServiceProvider
{
    const MODE_NORMAL = 1;
    const MODE_ON_VIEW = 2;

    public static $default_options = [
       'timetracking.mode' => self::MODE_NORMAL,
       'timetracking.interactive' => true,
       'timetracking.timelog_dialog' => true,
       'timetracking.allow_reset' => true,
       'timetracking.show_timelogs' => true,
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
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(TIMETR_MODULE).'/css/module.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(TIMETR_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(TIMETR_MODULE).'/js/module.js';
            return $javascripts;
        });

        // JS messages
        \Eventy::addAction('js.lang.messages', function() {
            ?>
                "time_tracking": "<?php echo __("Time Tracking") ?>",
                "timetr_modal_text": "<?php echo __("This is the amount of time you spent on this ticket. Edit the time, if needed, and then click Submit Time. Click Cancel if you don't want to save the time spent.") ?>",
                "submit_time": "<?php echo __("Submit Time") ?>",
            <?php
        });

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            if (\Route::is('conversations.view')) {
                echo 'initTimeTracking('.(int)\Option::get('timetracking.timelog_dialog', true).');';
            }
        });

        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['timetracking'] = ['title' => __('Time Tracking'), 'icon' => 'time', 'order' => 300];

            return $sections;
        }, 17);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != 'timetracking') {
                return $settings;
            }
           
            $settings = \Option::getOptions([
                'timetracking.mode',
                'timetracking.interactive',
                'timetracking.timelog_dialog',
                'timetracking.allow_reset',
                'timetracking.show_timelogs',
            ], self::$default_options);

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != 'timetracking') {
                return $params;
            }

            $params = [
                'template_vars' => [
                    'active'           => \Option::get('slack.active'),
                ],
                'settings' => [
                    'timetracking.mode' => [
                        'default' => self::MODE_NORMAL
                    ],
                    'timetracking.interactive' => [
                        'default' => true
                    ],
                    'timetracking.timelog_dialog' => [
                        'default' => true
                    ],
                    'timetracking.allow_reset' => [
                        'default' => true
                    ],
                    'timetracking.show_timelogs' => [
                        'default' => true
                    ],
                ]
            ];

            return $params;
        }, 20, 2);


        // Settings view name
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != 'timetracking') {
                return $view;
            } else {
                return 'timetracking::settings';
            }
        }, 20, 2);

        // Show block in conversation
        \Eventy::addAction('conversation.after_subject_block', function($conversation, $mailbox) {
            // if (!$conversation->user_id) {
            //     return;
            // }
            $options = \Option::getOptions(
                [
                    'timetracking.interactive',
                    'timetracking.allow_reset',
                    'timetracking.show_timelogs',
                ],
                self::$default_options
            );
            
            $interactive = $options['timetracking.interactive'];
            $allow_reset = $options['timetracking.allow_reset'];
            $show_timelogs = $options['timetracking.show_timelogs'];

            $user = Auth()->user();

            $show_timer = true;
            if ($conversation->user_id != $user->id
                || in_array($conversation->status, [Conversation::STATUS_CLOSED, Conversation::STATUS_SPAM])
            ) {
                $show_timer = false;
            }

            if (!$user->isAdmin() && !$show_timer && !$show_timelogs) {
                return;
            }

            // Get times.
            $timelogs = Timelog::where('conversation_id', $conversation->id)
                ->orderBy('id', 'desc')
                ->get();

            $cur_time = 0;
            $cur_timelog = $timelogs->firstWhere('finished', false);
            $paused = false;
            $total_time = $timelogs->sum('time_spent');

            if ($cur_timelog) {
                $cur_time = $cur_timelog->time_spent;
                if (!$cur_timelog->paused) {
                    if (!$cur_time || $cur_timelog->created_at != $cur_timelog->updated_at) {
                        $part_time = $cur_timelog->calcTimeSpent();
                        $cur_time += $part_time;
                        $total_time += $part_time;
                    }
                }
                if ($cur_timelog->paused) {
                    $paused = true;
                }
            }

            $total_time_formatted = self::formatTime($total_time);

            // Remove current time
            foreach ($timelogs as $key => $timelog) {
                if (!$timelog->finished) {
                    $timelogs->forget($key);
                }
            }

            // Do not show time if there are no timelogs and timer is not running.
            if (!count($timelogs) && 
                ($conversation->user_id != $user->id || in_array($conversation->status, [Conversation::STATUS_CLOSED, Conversation::STATUS_SPAM]))
            ) {
                return;
            }

            ?>
                <div class="conv-top-block <?php if ($interactive): ?> interactive<?php endif ?>" id="time-tracking" data-current-time="<?php echo $cur_time; ?>" data-total-time="<?php echo $total_time; ?>" data-paused="<?php echo (int)$paused; ?>">
                    <?php if ($show_timer): ?>
                        <strong id="timetr-time">
                            <?php echo self::formatTime($cur_time); ?>
                        </strong>
                        <?php if ($interactive): ?>
                            <span class="tt-buttons">
                                <button type="button" id="timetr-start" class="btn btn-default btn-sm <?php if (!$paused): ?>disabled<?php endif ?>" <?php if (!$paused): ?>disabled<?php endif ?>><i class="glyphicon glyphicon-play"></i></button>
                                <button type="button" id="timetr-pause" class="btn btn-default btn-sm <?php if ($paused): ?>disabled<?php endif ?>" <?php if ($paused): ?>disabled<?php endif ?> ><i class="glyphicon glyphicon-pause"></i></button>
                                <?php if ($allow_reset): ?>
                                    <button type="button" id="timetr-reset" class="btn btn-default btn-sm" data-toggle="tooltip" title="<?php echo __('Reset Time'); ?>" data-loading-text="..."><i class="glyphicon glyphicon-repeat"></i></button>
                                <?php endif ?>
                            </span>
                        <?php endif ?>    
                    <?php endif ?>
                    <span>
                        <?php if (!$show_timer): ?><i class="glyphicon glyphicon-time text-help" data-toggle="tooltip" title="<?php echo __('Time Tracking'); ?>"></i> <?php endif ?><?php if (!$interactive): ?>&nbsp;<?php endif ?>
                        <?php echo __('Total time') ?>: <span class="text-help" id="timetr-total"><?php echo $total_time_formatted ?></span>
                    </span>
                    <?php if (($show_timelogs || $user->isAdmin()) && count($timelogs)): ?>
                        <a href="#" class="link-grey dropdown-toggle" id="timelogs-trigger"><span><?php echo __('Timelogs') ?></span> <b class="caret"></b></a>
                        <div id="timelogs" class="hidden">
                            <table class="table">
                                <tr>
                                    <th>
                                        <?php echo __('Status'); ?>
                                    </th>
                                    <th width="60%">
                                        <?php echo __('User'); ?>
                                    </th>
                                    <th>
                                        <?php echo __('Time'); ?>
                                    </th>
                                </tr>
                                <?php foreach ($timelogs as $timelog): ?>
                                    <tr>
                                        <td>
                                            <button type="button" class="btn btn-<?php echo Conversation::$status_classes[$timelog->conversation_status] ?> btn-light btn-xs" readonly="readonly" data-toggle="tooltip" title="<?php echo Conversation::statusCodeToName($timelog->conversation_status); ?>"><i class="glyphicon glyphicon-<?php echo Conversation::$status_icons[$timelog->conversation_status] ?>"></i></button>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($timelog->user->getFullName()); ?>
                                        </td>
                                        <td>
                                            <span data-toggle="tooltip" title="<?php echo \App\User::dateFormat($timelog->created_at); ?>">
                                                <?php echo self::formatTime($timelog->time_spent); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            <?php
        }, 20, 2);

        // Assignee changed
        \Eventy::addAction('conversation.user_changed', function($conversation, $by_user, $prev_user_id) {
            if (\Option::get('timetracking.mode') == self::MODE_NORMAL) {
                // Stop timer
                self::finishTimer($conversation, $prev_user_id);
                // Start timer
                self::startTimer($conversation, $conversation->user_id);
            }
        }, 20, 3);

        // Status changed
        \Eventy::addAction('conversation.status_changed', function($conversation, $by_user, $changed_on_reply, $prev_status) {
            if ($conversation->status == Conversation::STATUS_CLOSED || $conversation->status == Conversation::STATUS_SPAM) {
                // Stop timer
                self::finishTimer($conversation);
            } else {
                // Start timer
                self::startTimer($conversation);
            }
        }, 20, 4);

        // Start timer on conversation view.
        \Eventy::addAction('conversation.view.start', function($conversation) {
            if (\Option::get('timetracking.mode') == self::MODE_ON_VIEW) {
                $user = Auth()->user();
                if ($conversation->user_id == $user->id) {
                    // Start timer
                    self::startTimer($conversation);
                }
            }
        });

        // Start timer on conversation view.
        \Eventy::addAction('conversation.view.focus', function($conversation) {
            if (\Option::get('timetracking.mode') == self::MODE_ON_VIEW) {
                $user = Auth()->user();
                if ($conversation->user_id == $user->id) {
                    // Start timer
                    self::startTimer($conversation);
                }
            }
        });
        
        // Stop timer on conversation view finish.
        \Eventy::addAction('conversation.view.finish', function($conversation_id, $user_id) {

            if (\Option::get('timetracking.mode') == self::MODE_ON_VIEW) {
                $conversation = Conversation::find($conversation_id);

                if ($conversation && $conversation->user_id == $user_id) {
                    // Pause timer
                    self::pauseTimer($conversation, $user_id);
                }
            }
        }, 20, 2);

        // Preload tags for all conversations in the table
        \Eventy::addFilter('conversations_table.preload_table_data', function($conversations) {
            if (\Option::get('timetracking.mode') == self::MODE_ON_VIEW) {
                return $conversations;
            }
            $ids = $conversations->pluck('id')->unique()->toArray();
            if (!$ids) {
                return $conversations;
            }

            $user = Auth()->user();

            $timelogs = Timelog::whereIn('conversation_id', $ids)
                ->where('finished', false)
                ->where('user_id', $user->id)
                ->get();
            if (!count($timelogs)) {
                return $conversations;
            }

            foreach ($conversations as $i => $conversation) {
                // Find conversation timelogs
                foreach ($timelogs as $timelog) {
                    if ($timelog->conversation_id == $conversation->id) {
                        $conversation->timelog = $timelog;
                        break;
                    }
                }
            }

            return $conversations;
        });

        // Show tags in the conersations table
        \Eventy::addAction('conversations_table.after_subject', function($conversation) {
            // Show conversation tags
            if (!empty($conversation->timelog)) {
                echo '&nbsp;';
                if ($conversation->timelog->paused) {
                    echo '<i class="glyphicon glyphicon-pause text-danger" data-toggle="tooltip" title="'.__('Time Tracking').'"></i>';
                } else {
                    echo '<i class="glyphicon glyphicon-play text-success" data-toggle="tooltip" title="'.__('Time Tracking').'"></i>';
                }
            }
        });

        // Information on viewed conversations is stored in cache which is not 100% reliable.
        // So we need to 
        \Eventy::addFilter('schedule', function($schedule) {
            if (\Option::get('timetracking.mode') != self::MODE_ON_VIEW) {
                return $schedule;
            }

            // Make sure that all active timelogs have information in the cache.
            $active_timelogs = Timelog::where('finished', false)
                ->where('paused', false)
                ->get();

            $cache_data = \Cache::get('conv_view');

            if (!is_array($cache_data)) {
                $cache_data = [];
            }

            foreach ($active_timelogs as $timelog) {
                $force_pause = true;
                foreach ($cache_data as $conversation_id => $conv_data) {
                    foreach ($conv_data as $user_id => $data) {
                        if (!isset($data['t']) || !isset($data['r'])) {
                            continue;
                        }
                        if ($conversation_id == $timelog->conversation_id && $user_id == $timelog->user_id) {
                            $force_pause = false;
                            break 2;
                        }
                    }
                }

                if ($force_pause) {
                    $timelog->pause();
                }
            }

            return $schedule;
        });
    }

    /**
     * Pause the timer.
     */
    public static function pauseTimer($conversation, $for_user_id = null)
    {
        $timelog = Timelog::where('conversation_id', $conversation->id)
            ->where('finished', false);
        if ($for_user_id) {
            $timelog->where('user_id', $for_user_id);
        }
        $timelog = $timelog->first();

        if ($timelog && !$timelog->paused) {
            $timelog->pause();
        }
    }

    /**
     * Stop time for user_id.
     */
    public static function finishTimer($conversation, $for_user_id = null)
    {
        $timelog = Timelog::where('conversation_id', $conversation->id)
            ->where('finished', false);
        if ($for_user_id) {
            $timelog->where('user_id', $for_user_id);
        }
        $timelog = $timelog->first();

        if ($timelog) {
            $timelog->finished = true;

            if (!$timelog->paused) {
                $timelog->time_spent += $timelog->calcTimeSpent();
            }
            $timelog->paused = false;
            $timelog->save();
        }
    }

    /**
     * Starts or resumes the times.
     */
    public static function startTimer($conversation)
    {
        if (!$conversation->user_id 
            || in_array($conversation->status, [Conversation::STATUS_CLOSED, Conversation::STATUS_SPAM])
            || $conversation->state != Conversation::STATE_PUBLISHED
        ) {
            return false;
        }
        // Get active
        $timelog = Timelog::where('conversation_id', $conversation->id)
            ->where('finished', false)
            ->first();
        if ($timelog) {
            // Already started.
            // Some strange situation.
            if ($timelog->user_id != $conversation->user_id) {
                $timelog->user_id = $conversation->user_id;
                $timelog->save();
            }
            if ($timelog->paused) {
                $timelog->paused = false;
                $timelog->save();
            }
            return $timelog;
        }

        $timelog = new Timelog;
        $timelog->finished = false;
        $timelog->conversation_id = $conversation->id;
        $timelog->user_id = $conversation->user_id;
        $timelog->conversation_status = $conversation->status;
        $timelog->paused = false;
        $timelog->save();

        return $timelog;
    }

    public static function formatTime($time)
    {
        $hours = sprintf("%02d", floor($time / 3600));
        $minutes = sprintf("%02d", floor(floor($time / 60) % 60));
        $seconds = sprintf("%02d", $time % 60);

        return $hours.':'.$minutes.':'.$seconds;
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
            __DIR__.'/../Config/config.php' => config_path('timetracking.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'timetracking'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/timetracking');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/timetracking';
        }, \Config::get('view.paths')), [$sourcePath]), 'timetracking');
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
