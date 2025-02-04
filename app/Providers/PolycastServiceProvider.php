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

        $this->app['router']->group(['middleware' => ['web'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'App\Http\Controllers'], function ($router) {

            // establish connection and send current time
            $this->app['router']->post('polycast/connect', 'SecureController@polycastRouteConnect');

            // send payloads to requested browser
            $this->app['router']->post('polycast/receive', 'SecureController@polycastRouteReceive');
        });
    }

    /**
     * Process conversation viewers.
     */
    public static function processConvView($request)
    {
        // Periodically save info indicating that user is still viewing the conversation.
        $viewing_conversation_id = null;
        if (!empty($request->data) && !empty($request->data['conversation_id'])) {
            $viewing_conversation_id = $request->data['conversation_id'];

            if (!empty($request->data['conversation_view_focus'])) {
                $conversation = Conversation::find($request->data['conversation_id']);
                if ($conversation) {
                    \Eventy::action('conversation.view.focus', $conversation);
                }
            }
        }
        if ($viewing_conversation_id) {

            $user = auth()->user();
            $now = Carbon::now();
            
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
                    if (!(int)$cache_data['r'] && (int)$request->data['replying']) {
                        // Started to reply.
                        \App\Events\RealtimeConvView::dispatchSelf($viewing_conversation_id, $user, true);
                        $replying_changed = true;
                    } elseif ((int)$cache_data['r'] && !(int)$request->data['replying']) {
                        // Finished to reply.
                        \App\Events\RealtimeConvView::dispatchSelf($viewing_conversation_id, $user, false);
                        $replying_changed = true;
                    }
                } else {
                    $replying_changed = true;
                }
            }
            
            if (!$cache_data || $replying_changed || ($view_date && $now->diffInSeconds($view_date) > 15)) {
                // Remember date of the last view in the cache.
                // Store for 2 minutes.
                $cache_data = [
                    't' => $now->toDateTimeString(),
                    'r' => (int)$request->data['replying']
                ];
                \Cache::put($cache_key, $cache_data, 1);

                // Job could not detect when user finishes to view converrsation.
                // We are using cron.
                // \App\Jobs\CheckConvView::dispatch($viewing_conversation_id, $user->id)
                //     ->delay(now()->addSeconds(25))
                //     ->onQueue(\Helper::QUEUE_DEFAULT);

                $conv_key = 'conv_view';
                $conv_data = \Cache::get($conv_key) ?? [];
                $conv_data[$viewing_conversation_id][$user->id] = $cache_data;
                \Cache::put($conv_key, $conv_data, 20 /*minutes*/);
                
                // \DB::table('polycast_events')->insert([
                //     'channels'   => json_encode([['name' => 'conv.view']]),
                //     'event'      => 'App\Events\RealtimeConvView',
                //     'payload'    => json_encode([
                //         'conversation_id' => $viewing_conversation_id,
                //         'reiterating' => true
                //     ]),
                //     'created_at' => Carbon::now()->toDateTimeString(),
                // ]);
            }
        }
    }

    public function register()
    {
    }
}
