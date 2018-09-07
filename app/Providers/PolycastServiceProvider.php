<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 30/10/15
 * Time: 19:34
 */

namespace App\Providers;


use Carbon\Carbon;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class PolycastServiceProvider extends ServiceProvider
{
    public function boot(Factory $factory){

        // register the polycast driver
        $factory->extend('polycast', function(/*Application $app*/){
            return new PolycastBroadcaster();
        });

        // $this->publishes([
        //     __DIR__.'/../dist/js/polycast.js' => public_path('vendor/polycast/polycast.js'),
        //     __DIR__.'/../dist/js/polycast.min.js' => public_path('vendor/polycast/polycast.min.js'),
        // ], 'public');

        // $this->publishes([
        //     __DIR__.'/../migrations/' => database_path('migrations')
        // ], 'migrations');

        // establish connection and send current time
        $this->app['router']->post('polycast/connect', function(){
            return ['status' => 'success', 'time' => Carbon::now()->toDateTimeString()];
        });

        // send payloads to requested browser
        $this->app['router']->post('polycast/receive', function(Request $request){

            $query = \DB::table('polycast_events')
                ->select('*');

            $channels = $request->get('channels', []);

            foreach($channels as $channel => $events){
                foreach($events as $event) {
                    $query->orWhere(function($query) use ($channel, $event, $request){
                        $query->where('channels', 'like', '%"'.$channel.'"%')
                            ->where('event', '=', $event)
                            ->where('created_at', '>=', $request->get('time'));
                    });
                }
            }

            $collection = collect($query->get());
            $payload = $collection->map(function ($item, $key) use ($request) {
                $created = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $item->created_at);
                $requested = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $request->get('time'));
                $item->channels = json_decode($item->channels);
                $item->payload = json_decode($item->payload);
                $item->delay = $requested->diffInSeconds($created);
                $item->requested_at = $requested->toDateTimeString();
                return $item;
            });

            return ['status' => 'success', 'time' => Carbon::now()->toDateTimeString(), 'payloads' => $payload];
        });

    }

    public function register(){

    }

}