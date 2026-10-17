<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 30/10/15
 * Time: 19:34.
 */

namespace App\Providers;

use App\Conversation;
use App\Notifications\BroadcastNotification;
use Carbon\Carbon;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class PolycastServiceProvider extends ServiceProvider
{
    //public function boot(BroadcastingFactory $factory){
    public function boot()
    {

        // register the polycast driver
        // $factory->extend('polycast', function(/*Application $app*/){
        //     return new PolycastBroadcaster();
        // });
        //$factory->extend('polycast', function (Application $app, $config) {
        //app('Illuminate\Broadcasting\BroadcastManager')->extend('polycast', function (array $config) {
        //$broadcastManager->extend('polycast', function (array $config) {

        // This has to be done in BroadcastServiceProvider to avoid "Driver [driver] is not supported" error
        // $this->app[BroadcastManager::class]->extend('polycast', function (array $config) {
        //     echo 'we are in extend';
        //     return new \App\Misc\PolycastBroadcaster();
        // });

        // $this->publishes([
        //     __DIR__.'/../dist/js/polycast.js' => public_path('vendor/polycast/polycast.js'),
        //     __DIR__.'/../dist/js/polycast.min.js' => public_path('vendor/polycast/polycast.min.js'),
        // ], 'public');

        // $this->publishes([
        //     __DIR__.'/../migrations/' => database_path('migrations')
        // ], 'migrations');

        $this->app['router']->group(['middleware' => ['web'], 'prefix' => \Helper::getSubdirectory()], function ($router) {

            // establish connection and send current time
            $this->app['router']->post('polycast/connect', function (Request $request) {
                \Broadcast::auth($request);

                return ['status' => 'success', 'time' => Carbon::now()->toDateTimeString()];
            });

            // send payloads to requested browser
            $this->app['router']->post('polycast/receive', function (Request $request) {
                \Broadcast::auth($request);

                $query = \DB::table('polycast_events')
                    ->select('*');

                $channels = $request->get('channels', []);

                // $request->channels must contain a list of channels.
                // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-7x4c-p73f-67x5
                if (empty($channels)) {
                    \Helper::denyAccess();
                }

                foreach ($channels as $channel => $events) {

                    // https://github.com/freescout-help-desk/freescout/security/advisories/GHSA-gh3r-jh6q-wrvj
                    $channel = \Helper::sqlEscapeLike($channel);

                    foreach ($events as $event) {
                        // No need to add index to DB for this query.
                        $query->orWhere(function ($query) use ($channel, $event, $request) {
                            $query->where('channels', 'like', '%"'.$channel.'"%')
                                ->where('event', '=', $event)
                                // Recors are fetched starting from opening the page or from the last event.
                                ->where('created_at', '>=', $request->get('time'));
                        });
                    }
                }

                $collection = collect($query->get());

                $payload = $collection->map(function ($item, $key) use ($request) {
                    $created = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at);
                    $requested = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $request->get('time'));
                    $item->channels = json_decode($item->channels, false);
                    $item->payload = json_decode($item->payload, false);
                    // Add extra data to the payload - used in RealtimeBroadcastNotificationCreated.
                    // This works only if payload has medius and thread_id
                    $item->data = BroadcastNotification::fetchPayloadData($item->payload);

                    $event_class = '\\'.$item->event;
                    if (method_exists($event_class, "processPayload")) {
                        // If user is not allowed to access this event, payload will be set to empty array.
                        $item->payload = $event_class::processPayload($item->payload);

                        if (!$item->payload) {
                            $item->data = [];
                        }
                    }

                    $item->delay = $requested->diffInSeconds($created);
                    $item->requested_at = $requested->toDateTimeString();

                    return $item;
                });

                // Reflash session data - otherwise on reply flash alert is not displayed
                // https://stackoverflow.com/questions/37019294/laravel-ajax-call-deletes-session-flash-data
                \Session::reflash();

                $this->processConvView($request);

                \Eventy::action('polycast.receive', $request, $collection, $payload);

                return ['status' => 'success', 'time' => Carbon::now()->toDateTimeString(), 'payloads' => $payload];
            });
        });
    }

    /**
     * Sanitize conversation ID received from the browser.
     * Only a positive integer is accepted, everything else is ignored.
     *
     * @return int|null
     */
    public static function sanitizeConversationId($value)
    {
        if (!is_scalar($value) || is_bool($value)) {
            return null;
        }
        $value = (string)$value;

        // MySQL INT UNSIGNED fits into 10 digits.
        if (!ctype_digit($value) || strlen($value) > 10) {
            return null;
        }
        $value = (int)$value;

        return $value > 0 ? $value : null;
    }

    /**
     * Check if the user is allowed to view the conversation.
     *
     * @param Conversation|null $conversation
     * @param \App\User|null    $user
     *
     * @return bool
     */
    public static function canView($conversation, $user)
    {
        return $conversation && $user && $user->can('view', $conversation);
    }

    /**
     * Process conversation viewers.
     */
    public function processConvView($request)
    {
        $data = $request->data;
        if (empty($data) || !is_array($data) || empty($data['conversation_id'])) {
            return;
        }

        // Conversation ID is untrusted: it must be a positive integer.
        $viewing_conversation_id = self::sanitizeConversationId($data['conversation_id']);
        if (!$viewing_conversation_id) {
            return;
        }

        $user = auth()->user();
        if (!$user) {
            return;
        }

        $replying = !empty($data['replying']) ? 1 : 0;
        $focus = !empty($data['conversation_view_focus']);
        $now = Carbon::now();

        // Periodically save info indicating that user is still viewing the conversation.
        $cache_key = 'conv_view_'.$user->id.'_'.$viewing_conversation_id;
        $cache_data = \Cache::get($cache_key);
        $view_date = null;
        $replying_changed = false;

        // t - date.
        // r - replying.
        if ($cache_data) {
            if (isset($cache_data['t']) && isset($cache_data['r'])) {
                $view_date = Carbon::createFromFormat('Y-m-d H:i:s', $cache_data['t']);

                // Let other users know that user started to reply.
                if (!(int)$cache_data['r'] && $replying) {
                    // Started to reply.
                    \App\Events\RealtimeConvView::dispatchSelf($viewing_conversation_id, $user, true);
                    $replying_changed = true;
                } elseif ((int)$cache_data['r'] && !$replying) {
                    // Finished to reply.
                    \App\Events\RealtimeConvView::dispatchSelf($viewing_conversation_id, $user, false);
                    $replying_changed = true;
                }
            } else {
                $replying_changed = true;
            }
        }

        $need_save = !$cache_data || $replying_changed || ($view_date && $now->diffInSeconds($view_date) > 15);

        // The conversation is loaded and access is checked only if needed.
        $conversation = null;
        $can_view = false;
        if ($focus || $need_save) {
            $conversation = Conversation::find($viewing_conversation_id);
            $can_view = self::canView($conversation, $user);
        }

        if ($focus && $can_view) {
            \Eventy::action('conversation.view.focus', $conversation);
        }

        if ($need_save) {
            // Nothing is stored for conversations which do not exist
            // or which the user is not allowed to view.
            if (!$can_view) {
                return;
            }

            // Remember date of the last view in the cache.
            // Store for 2 minutes.
            $cache_data = [
                't' => $now->toDateTimeString(),
                'r' => $replying,
            ];
            \Cache::put($cache_key, $cache_data, 1);

            // Job could not detect when user finishes to view converrsation.
            // We are using cron.
            $conv_key = 'conv_view';
            $conv_data = \Cache::get($conv_key);
            if (!is_array($conv_data)) {
                $conv_data = [];
            }
            $conv_data[$viewing_conversation_id][$user->id] = $cache_data;
            \Cache::put($conv_key, $conv_data, 20 /*minutes*/);
        }
    }

    public function register()
    {
    }
}
