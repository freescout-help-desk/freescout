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

    public static function create($customer_id, $channel, $channel_id)
    {
        try {
            $customer_channel = new self();
            $customer_channel->customer_id = $customer_id;
            $customer_channel->channel     = $channel;
            $customer_channel->channel_id  = $channel_id;
            $customer_channel->save();
        } catch (\Exception $e) {
            // Already exists.
        }
    }

    public function getChannelName()
    {
        return \Eventy::filter('channel.name', '', $this->channel);
    }
}
