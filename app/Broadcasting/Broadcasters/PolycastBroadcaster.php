<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 31/10/15
 * Time: 00:20.
 */

namespace App\Broadcasting\Broadcasters;

use Carbon\Carbon;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PolycastBroadcaster extends Broadcaster
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

    /**
     * Broadcast is called when the queued job is processed.
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        // delete events older than two minutes
        \DB::table('polycast_events')->where('created_at', '<', Carbon::now()->subMinutes($this->delete_old)->toDateTimeString())->delete();

        // insert the new event
        \DB::table('polycast_events')->insert([
            'channels'   => json_encode($channels),
            'event'      => $event,
            'payload'    => json_encode($payload),
            'created_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function auth($request)
    {

        // For connect request
        if (empty($request->channels)) {
            return true;
        }

        // Check all channels
        foreach ($request->channels as $channel_name => $channel_info) {
            // Copied from Illuminate\Broadcasting\Broadcasters\PusherBroadcaster
            if (Str::startsWith($channel_name, ['private-', 'presence-']) &&
                !$request->user()) {
                echo 1;
                exit();

                throw new AccessDeniedHttpException();
            }

            $channelName = Str::startsWith($channel_name, 'private-')
                                ? Str::replaceFirst('private-', '', $channel_name)
                                : Str::replaceFirst('presence-', '', $channel_name);
            // This throws an exception
            parent::verifyUserCanAccessChannel(
                $request, $channelName
            );
        }

        return true;
    }

    /**
     * Return the valid authentication response.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed                    $result
     *
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        // By some reason this is never called
        return false;

        // Copied from Illuminate\Broadcasting\Broadcasters\RedisBroadcaster
        // if (is_bool($result)) {
        //     return json_encode($result);
        // }

        // return json_encode(['channel_data' => [
        //     'user_id' => $request->user()->getAuthIdentifier(),
        //     'user_info' => $result,
        // ]]);
    }

    public function isDeferred()
    {
        return false;
    }

    /*
     * Created as there was an error:
     * "Call to undefined method App\Broadcasting\Broadcasters\PolycastBroadcaster::channel()"
     *
     * It is called from routes/channels.php
     */
     // public function channel($channel, callable $callback)
     // {
     //    return true;
     //    //return (int) $user->id === (int) $id;
     // }
}
