<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 31/10/15
 * Time: 00:20
 */

namespace App\Misc;


use Carbon\Carbon;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Foundation\Application;
use Illuminate\Database\DatabaseManager;

class PolycastBroadcaster implements Broadcaster
{
    //private $db = null;

    /**
     * Delete old events after 2 minutes.
     */
    private $delete_old = 2;

    public function __construct()
    {
        //$this->db = $app['db'];
        if (\Config::get('broadcasting.connections.polycast.delete_old')) {
            $this->delete_old = \Config::get('broadcasting.connections.polycast.delete_old');
        }
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        // delete events older than two minutes
        \DB::table('polycast_events')->where('created_at', '<', Carbon::now()->subMinutes($this->delete_old)->toDateTimeString())->delete();

        // insert the new event
        \DB::table('polycast_events')->insert([
            'channels' => json_encode($channels),
            'event' => $event,
            'payload' => json_encode($payload),
            'created_at' => Carbon::now()->toDateTimeString()
        ]);
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function auth($request) {
        // Copied from Illuminate\Broadcasting\Broadcasters\PusherBroadcaster
        if (Str::startsWith($request->channel_name, ['private-', 'presence-']) &&
            ! $request->user()) {
            throw new AccessDeniedHttpException;
        }

        $channelName = Str::startsWith($request->channel_name, 'private-')
                            ? Str::replaceFirst('private-', '', $request->channel_name)
                            : Str::replaceFirst('presence-', '', $request->channel_name);

        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result) {
        // Copied from Illuminate\Broadcasting\Broadcasters\RedisBroadcaster
        if (is_bool($result)) {
            return json_encode($result);
        }

        return json_encode(['channel_data' => [
            'user_id' => $request->user()->getAuthIdentifier(),
            'user_info' => $result,
        ]]);
    }

     public function isDeferred()
     {
        return false;
     }
}