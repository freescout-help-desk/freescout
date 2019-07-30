<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckConvViewers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:check-conv-viewers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if user finished viewing conversation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Check dates in cache.
        $cache_key = 'conv_view';
        $cache_data = \Cache::get($cache_key);

        if (empty($cache_data) || !is_array($cache_data)) {
            return;
        }

        $now = Carbon::now();
        $need_update = false;
        foreach ($cache_data as $conversation_id => $conv_data) {
            if (empty($conv_data) || !is_array($conv_data)) {
                continue;
            }
            foreach ($conv_data as $user_id => $data) {

                if (!isset($data['t']) || !isset($data['r'])) {
                    continue;
                }

                $view_date = Carbon::createFromFormat('Y-m-d H:i:s', $data['t']);
            
                if ($view_date && $now->diffInSeconds($view_date) > 25) {
                    // Remove user from viewers.
                    unset($cache_data[$conversation_id][$user_id]);
                    if (empty($cache_data[$conversation_id])) {
                        unset($cache_data[$conversation_id]);
                    }
                    $need_update = true;

                    // Create event to let other users know that user finished viewing conversation.
                    $notification_data = [
                        'conversation_id' => $conversation_id,
                        'user_id'         => $user_id,
                    ];
                    event(new \App\Events\RealtimeConvViewFinish($notification_data));

                    \Eventy::action('conversation.view.finish', $conversation_id, $user_id, $now->diffInSeconds($view_date));
                }
            }
        }

        if ($need_update) {
            // Update conversation cache data.
            \Cache::put($cache_key, $cache_data, 20 /*minutes*/);
        }
        /*$cache_key = 'conv_view_'.$this->user_id.'_'.$this->conversation_id;
        $cache_data = \Cache::get($cache_key);

        if (!isset($cache_data['t']) || !isset($cache_data['r'])) {
            return;
        }

        $view_date = Carbon::createFromFormat('Y-m-d H:i:s', $cache_data['t']);
        $now = Carbon::now();

        if ($view_date && $now->diffInSeconds($view_date) > 30) {
            $cache_key = 'conv_view';
            if (!empty($cache_data[$this->conversation_id]) && !empty($cache_data[$this->conversation_id][$this->user_id])) {
                // Remove user from viewers.
                unset($cache_data[$this->conversation_id][$this->user_id]);

                // Update conversation cache data.
                \Cache::put($cache_key, $cache_data, 1);
            }

            // Create event to let other users know that user finished viewing conversation.
            $notification_data = [
                'conversation_id' => $conversation->id,
                'user_id'         => $user->id,
            ];
            event(new \App\Events\RealtimeConvViewFinish($notification_data));
        }*/
    }
}
