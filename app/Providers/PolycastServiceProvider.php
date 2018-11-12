<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 30/10/15
 * Time: 19:34.
 */

namespace App\Providers;

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

        $this->app['router']->group(['middleware' => ['web']], function ($router) {

            // establish connection and send current time
            $this->app['router']->post('polycast/connect', function (Request $request) {
                return ['status' => 'success', 'time' => Carbon::now()->toDateTimeString()];
            });

            // send payloads to requested browser
            $this->app['router']->post('polycast/receive', function (Request $request) {
                \Broadcast::auth($request);

                $query = \DB::table('polycast_events')
                    ->select('*');

                $channels = $request->get('channels', []);

                foreach ($channels as $channel => $events) {
                    foreach ($events as $event) {
                        // No need to add index to DB for this query.
                        $query->orWhere(function ($query) use ($channel, $event, $request) {
                            $query->where('channels', 'like', '%"'.$channel.'"%')
                                ->where('event', '=', $event)
                                ->where('created_at', '>=', $request->get('time'));
                        });
                    }
                }

                $collection = collect($query->get());

                $payload = $collection->map(function ($item, $key) use ($request) {
                    $created = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at);
                    $requested = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $request->get('time'));
                    $item->channels = json_decode($item->channels);
                    $item->payload = json_decode($item->payload);
                    // Add extra data to the payload
                    $item->data = BroadcastNotification::fetchPayloadData($item->payload);

                    $item->delay = $requested->diffInSeconds($created);
                    $item->requested_at = $requested->toDateTimeString();

                    return $item;
                });

                // Reflash session data - otherwise on reply flash alert is not displayed
                // https://stackoverflow.com/questions/37019294/laravel-ajax-call-deletes-session-flash-data
                \Session::reflash();

                return ['status' => 'success', 'time' => Carbon::now()->toDateTimeString(), 'payloads' => $payload];
            });
        });
    }

    public function register()
    {
    }
}
