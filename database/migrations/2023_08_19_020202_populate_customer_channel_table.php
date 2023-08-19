<?php

use App\Customer;
use App\CustomerChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PopulateCustomerChannelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $i = 0;
        do {
            $customers = Customer::select(['id', 'channel', 'channel_id'])
                ->whereNotNull('channel')
                ->whereNotNull('channel_id')
                ->skip($i*100)
                ->limit(100)
                ->get();
            foreach ($customers as $customer) {
                if (!$customer->channel || !$customer->channel_id) {
                    continue;
                }
                try {
                    $customer_channel = new CustomerChannel();
                    $customer_channel->customer_id = $customer->id;
                    $customer_channel->channel = $customer->channel;
                    $customer_channel->channel_id = $customer->channel_id;
                    $customer_channel->save();
                } catch (\Exception $e) {
                    // Already exists.
                }
            }
            $i++;
        } while(count($customers));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        CustomerChannel::truncate();
    }
}
