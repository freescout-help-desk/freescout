<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerChannel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_channel';

    public $timestamps = false;

    /**
     * Cached communication channels.
     */
    public static $channels = null;

    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    public static function create($customer_id, $channel, $channel_id)
    {
        try {
            $customer_channel = new self();
            $customer_channel->customer_id = $customer_id;
            $customer_channel->channel     = $channel;
            $customer_channel->channel_id  = $channel_id;
            $customer_channel->save();

            return $customer_channel;
        } catch (\Exception $e) {
            // Already exists.
            return null;
        }
    }

    public function getChannelName()
    {
        return \Eventy::filter('channel.name', '', $this->channel);
    }

    public static function getChannels()
    {
        if (self::$channels !== null) {
            return self::$channels;
        } else {
            self::$channels = \Eventy::filter('channels.list', []);
            return self::$channels;
        }
    }
}
