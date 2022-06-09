<?php
/**
 * todo: implement caching by saving all options in one cache variable on register_shutdown_function.
 */

namespace App;

use App\Notifications\BroadcastNotification;
use App\Notifications\WebsiteNotification;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    // Event types
    // Changing ticket status does not fire event
    const EVENT_TYPE_NEW = 1;
    const EVENT_TYPE_ASSIGNED = 2;
    const EVENT_TYPE_UPDATED = 3;
    const EVENT_TYPE_CUSTOMER_REPLIED = 4;
    const EVENT_TYPE_USER_REPLIED = 5;
    const EVENT_TYPE_USER_ADDED_NOTE = 6;

    // Events
    // Notify me when…
    const EVENT_NEW_CONVERSATION = 1;
    const EVENT_CONVERSATION_ASSIGNED_TO_ME = 2;
    const EVENT_CONVERSATION_ASSIGNED = 6;
    const EVENT_FOLLOWED_CONVERSATION_UPDATED = 13;
    
    //const EVENT_MY_TEAM_MENTIONED = 15;
    // Notify me when a customer replies…
    const EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED = 4;
    const EVENT_CUSTOMER_REPLIED_TO_MY = 3;
    const EVENT_CUSTOMER_REPLIED_TO_ASSIGNED = 7;
    // Notify me when another a user replies or adds a note…
    const EVENT_USER_REPLIED_TO_UNASSIGNED = 8;
    const EVENT_USER_REPLIED_TO_MY = 5;
    const EVENT_USER_REPLIED_TO_ASSIGNED = 9;

    // Mediums
    const MEDIUM_EMAIL = 1; // This is also website notifications
    const MEDIUM_BROWSER = 2; // Browser push notification
    const MEDIUM_MOBILE = 3;
    const MEDIUM_MENU = 10; // Notifications menu

    public static $mediums = [
        self::MEDIUM_EMAIL,
        self::MEDIUM_BROWSER,
        self::MEDIUM_MOBILE,
    ];

    public static $default_subscriptions = [
        self::MEDIUM_EMAIL => [
            self::EVENT_CONVERSATION_ASSIGNED_TO_ME,
            self::EVENT_FOLLOWED_CONVERSATION_UPDATED,
            //self::EVENT_MY_TEAM_MENTIONED,
            self::EVENT_CUSTOMER_REPLIED_TO_MY,
            self::EVENT_USER_REPLIED_TO_MY,
        ],
        self::MEDIUM_BROWSER => [
            self::EVENT_CONVERSATION_ASSIGNED_TO_ME,
            self::EVENT_FOLLOWED_CONVERSATION_UPDATED,
            //self::EVENT_MY_TEAM_MENTIONED,
            self::EVENT_CUSTOMER_REPLIED_TO_MY,
            self::EVENT_USER_REPLIED_TO_MY,
        ],
    ];

    /**
     * List of events that occured.
     */
    public static $occured_events = [];

    public $timestamps = false;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Subscribed user.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Add default subscriptions for user.
     *
     * @param int $user_id
     */
    public static function addDefaultSubscriptions($user_id)
    {
        self::saveFromArray(self::getDefaultSubscriptions(), $user_id);
    }

    public static function getDefaultSubscriptions()
    {
        return Option::get('subscription_defaults', self::$default_subscriptions);
    }

    /**
     * Save subscriptions from passed array.
     *
     * @param array $subscriptions [description]
     *
     * @return [type] [description]
     */
    public static function saveFromArray($new_subscriptions, $user_id)
    {
        $subscriptions = [];

        if (is_array($new_subscriptions)) {
            foreach ($new_subscriptions as $medium => $events) {
                foreach ($events as $event) {
                    $subscriptions[] = [
                        'user_id' => $user_id,
                        'medium'  => $medium,
                        'event'   => $event,
                    ];
                }
            }
        }

        self::where('user_id', $user_id)->delete();
        self::insert($subscriptions);
    }

    /**
     * Check if subscription exists.
     */
    public static function exists(array $params, $subscriptions = null)
    {
        if ($subscriptions) {
            // Look in the passed list
            foreach ($subscriptions as $subscription) {
                foreach ($params as $param_name => $param_value) {
                    if ($subscription->$param_name != $param_value) {
                        continue 2;
                    }
                }

                return true;
            }
        } else {
            // Search in DB
        }

        return false;
    }

    /**
     * Detect users to notify by medium.
     */
    public static function usersToNotify($event_type, $conversation, $threads, $mailbox_user_ids = null)
    {
        $thread = $threads[0];

        // Ignore imported threads.
        if ($thread->imported) {
            return true;
        }

        // Detect events
        $events = [];

        $prev_thread = null;
        if (!empty($threads[1])) {
            $prev_thread = $threads[1];
        }

        switch ($event_type) {
            case self::EVENT_TYPE_NEW:
                $events[] = self::EVENT_NEW_CONVERSATION;
                break;

            case self::EVENT_TYPE_ASSIGNED:
                $events[] = self::EVENT_CONVERSATION_ASSIGNED_TO_ME;
                $events[] = self::EVENT_CONVERSATION_ASSIGNED;
                break;

            case self::EVENT_TYPE_CUSTOMER_REPLIED:
                $events[] = self::EVENT_FOLLOWED_CONVERSATION_UPDATED;
                if (!empty($prev_thread) && $prev_thread->user_id) {
                    $events[] = self::EVENT_CUSTOMER_REPLIED_TO_MY;
                    $events[] = self::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED;
                } else {
                    $events[] = self::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED;
                }
                break;

            case self::EVENT_TYPE_USER_REPLIED:
            case self::EVENT_TYPE_USER_ADDED_NOTE:
                $events[] = self::EVENT_FOLLOWED_CONVERSATION_UPDATED;
                if (!empty($prev_thread) && $prev_thread->user_id) {
                    $events[] = self::EVENT_USER_REPLIED_TO_MY;
                    $events[] = self::EVENT_USER_REPLIED_TO_ASSIGNED;
                } else {
                    $events[] = self::EVENT_USER_REPLIED_TO_UNASSIGNED;
                }
                break;
        }

        $events = \Eventy::filter('subscription.events_by_type', $events, $event_type, $thread);

        // Check if assigned user changed
        $user_changed = false;
        if ($event_type != self::EVENT_TYPE_ASSIGNED && $event_type != self::EVENT_TYPE_NEW) {
            if ($thread->type == Thread::TYPE_LINEITEM && $thread->action_type == Thread::ACTION_TYPE_USER_CHANGED) {
                $user_changed = true;
            } elseif ($prev_thread) {
                if ($prev_thread->user_id != $thread->user_id) {
                    $user_changed = true;
                }
            } else {
                // Get prev thread
                if ($prev_thread && $prev_thread->user_id != $thread->user_id) {
                    $user_changed = true;
                }
            }
        }
        if ($user_changed) {
            $events[] = self::EVENT_CONVERSATION_ASSIGNED_TO_ME;
            $events[] = self::EVENT_CONVERSATION_ASSIGNED;
            $events[] = self::EVENT_FOLLOWED_CONVERSATION_UPDATED;
        }
        $events = array_unique($events);

        // Detect subscribed users
        if (!$mailbox_user_ids) {
            $mailbox_user_ids = $conversation->mailbox->userIdsHavingAccess();
        }

        $subscriptions = self::whereIn('user_id', $mailbox_user_ids)
            ->whereIn('event', $events)
            ->get();

        $subscriptions = \Eventy::filter('subscription.subscriptions', $subscriptions, $conversation, $events);

        // Filter subscribers
        $users_to_notify = [];
        foreach ($subscriptions as $i => $subscription) {
            // Actions on conversation where user is assignee
            if (in_array($subscription->event, [self::EVENT_CONVERSATION_ASSIGNED_TO_ME, self::EVENT_CUSTOMER_REPLIED_TO_MY, self::EVENT_USER_REPLIED_TO_MY]) 
                && ($conversation->user_id != $subscription->user_id && !\Eventy::filter('subscription.is_user_assignee', false, $subscription, $conversation))
            ) {
                continue;
            }

            // Check if user is following this conversation.
            if ($subscription->event == self::EVENT_FOLLOWED_CONVERSATION_UPDATED 
                && !$conversation->isUserFollowing($subscription->user_id)
            ) {
                continue;
            }

            // Skip if user muted notifications for this mailbox
            if ($subscription->user->isAdmin()) {

                // Mute notifications for events not related directly to the user.
                if (!in_array($subscription->event, [self::EVENT_CONVERSATION_ASSIGNED_TO_ME, self::EVENT_FOLLOWED_CONVERSATION_UPDATED, self::EVENT_CUSTOMER_REPLIED_TO_MY, self::EVENT_USER_REPLIED_TO_MY])
                    && !\Eventy::filter('subscription.is_related_to_user', false, $subscription, $thread)
                ) {
                    $mailbox_settings = $conversation->mailbox->getUserSettings($subscription->user_id);

                    if (!empty($mailbox_settings->mute)) {
                        continue;
                    }
                }
            }

            if (\Eventy::filter('subscription.filter_out', false, $subscription, $thread)) {
                continue;
            }

            $users_to_notify[$subscription->medium][] = $subscription->user;
            $users_to_notify[$subscription->medium] = array_unique($users_to_notify[$subscription->medium]);
        }

        // Add menu notifications, for example.
        $users_to_notify = \Eventy::filter('subscription.users_to_notify', $users_to_notify, $event_type, $events, $thread);

        return $users_to_notify;
    }

    /**
     * Process events which occured.
     */
    public static function processEvents()
    {
        $notify = [];

        $delay = now()->addSeconds(Conversation::UNDO_TIMOUT);

        // Collect into notify array information about all users who need to be notified
        foreach (self::$occured_events as $event) {
            // Get mailbox users ids
            $mailbox_user_ids = [];
            foreach (self::$mediums as $medium) {
                if (!empty($notify[$medium])) {
                    foreach ($notify[$medium] as $conversation_id => $notify_info) {
                        if ($notify_info['conversation']->mailbox_id == $event['conversation']->mailbox_id) {
                            $mailbox_user_ids = $notify_info['mailbox_user_ids'];
                            break 2;
                        }
                    }
                }
            }

            // Get users and threads from previous results to avoid repeated SQL queries.
            $users = [];
            $threads = [];
            foreach (self::$mediums as $medium) {
                if (empty($notify[$medium][$event['conversation']->id])) {
                    $threads = $event['conversation']->getThreads();
                    break;
                } else {
                    $users = $notify[$medium][$event['conversation']->id]['users'];
                    $threads = $notify[$medium][$event['conversation']->id]['threads'];
                }
            }

            $users_to_notify = self::usersToNotify($event['event_type'], $event['conversation'], $threads, $mailbox_user_ids);

            if (!$users_to_notify || !is_array($users_to_notify)) {
                continue;
            }

            foreach ($users_to_notify as $medium => $medium_users_to_notify) {

                // Remove current user from recipients if action caused by current user
                foreach ($medium_users_to_notify as $i => $user) {
                    if ($user->id == $event['caused_by_user_id']) {
                        unset($medium_users_to_notify[$i]);
                    }
                }

                if (count($medium_users_to_notify)) {
                    $notify[$medium][$event['conversation']->id] = [
                        // Users subarray contains all users who need to receive notification for all events
                        'users'            => array_unique(array_merge($users, $medium_users_to_notify)),
                        'conversation'     => $event['conversation'],
                        'threads'          => $threads,
                        'mailbox_user_ids' => $mailbox_user_ids,
                    ];
                }
            }
        }

        // - Email notification (better to create them first)
        if (!empty($notify[self::MEDIUM_EMAIL])) {
            foreach ($notify[self::MEDIUM_EMAIL] as $conversation_id => $notify_info) {
                \App\Jobs\SendNotificationToUsers::dispatch($notify_info['users'], $notify_info['conversation'], $notify_info['threads'])
                    ->delay($delay)
                    ->onQueue('emails');
            }
        }

        // - Menu notification (uses same medium as for email)
        if (!empty($notify[self::MEDIUM_EMAIL]) || !empty($notify[self::MEDIUM_MENU])) {

            $notify_menu = ($notify[self::MEDIUM_EMAIL] ?? []) + ($notify[self::MEDIUM_MENU] ?? []);
            foreach ($notify_menu as $notify_info) {
                $website_notification = new WebsiteNotification($notify_info['conversation'], self::chooseThread($notify_info['threads']));
                $website_notification->delay($delay);
                \Notification::send($notify_info['users'], $website_notification);
            }
        }

        // Send broadcast notifications:
        // - Browser push notification
        $broadcasts = [];
        foreach ([self::MEDIUM_EMAIL, self::MEDIUM_BROWSER] as $medium) {
            if (empty($notify[$medium])) {
                continue;
            }
            foreach ($notify[$medium] as $notify_info) {
                $thread_id = self::chooseThread($notify_info['threads'])->id;

                foreach ($notify_info['users'] as $user) {
                    $mediums = [$medium];
                    if (!empty($broadcasts[$thread_id]['mediums'])) {
                        $mediums = array_unique(array_merge($mediums, $broadcasts[$thread_id]['mediums']));
                    }
                    $broadcasts[$thread_id] = [
                        'user'         => $user,
                        'conversation' => $notify_info['conversation'],
                        'threads'      => $notify_info['threads'],
                        'mediums'      => $mediums,
                    ];
                }
            }
        }
        // \Notification::sendNow($notify_info['users'], new BroadcastNotification($notify_info['conversation'], $notify_info['threads'][0]));
        foreach ($broadcasts as $thread_id => $to_broadcast) {
            $broadcast_notification = new BroadcastNotification($to_broadcast['conversation'], self::chooseThread($to_broadcast['threads']), $to_broadcast['mediums']);
            $broadcast_notification->delay($delay);
            $to_broadcast['user']->notify($broadcast_notification);
        }

        // - Mobile
        \Eventy::action('subscription.process_events', $notify);

        self::$occured_events = [];
    }

    /**
     * Get fist meaningful thread for the notification.
     */
    public static function chooseThread($threads)
    {
        $actions_types = [
            Thread::ACTION_TYPE_USER_CHANGED,
        ];
        // First thread is the newest.
        foreach ($threads as $thread) {
            if ($thread->type == Thread::TYPE_LINEITEM && !in_array($thread->action_type, $actions_types)) {
                continue;
            } else {
                return $thread;
            }
        }
        return $threads[0];
    }

    /**
     * Remember event type to process in ProcessSubscriptionEvents middleware on terminate.
     */
    public static function registerEvent($event_type, $conversation, $caused_by_user_id, $process_now = false)
    {
        self::$occured_events[] = [
            'event_type'        => $event_type,
            'conversation'      => $conversation,
            'caused_by_user_id' => $caused_by_user_id,
        ];

        // Automatically add EVENT_TYPE_UPDATED
        if (!in_array($event_type, [self::EVENT_TYPE_UPDATED, self::EVENT_TYPE_NEW])) {
            self::$occured_events[] = [
                'event_type'        => self::EVENT_TYPE_UPDATED,
                'conversation'      => $conversation,
                'caused_by_user_id' => $caused_by_user_id,
            ];
        }
        if ($process_now) {
            self::processEvents();
        }
    }
}
