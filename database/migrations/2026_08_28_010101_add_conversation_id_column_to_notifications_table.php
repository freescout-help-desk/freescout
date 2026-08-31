<?php

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConversationIdColumnToNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            // https://github.com/freescout-help-desk/freescout/issues/5600
            $table->unsignedInteger('conversation_id')->after('data')->nullable();
            // "read_at" is skipped on purpose - there is no use from it here.
            $table->index(['notifiable_id', 'notifiable_type', 'conversation_id'], 'notifications_conversation_id');
        });

        // Populate "conversation_id" field for notifications.
        
        try {
            // Try to populate "converesation_id" in one query.
            if (\Helper::isPgSql()) {
                $expression = "(data::json->>'conversation_id')::bigint";
            } else {
                $expression = "data->>'$.conversation_id'";
            }
            DatabaseNotification::whereNull('conversation_id')
                    ->where('data', 'like', '%"conversation_id":%')
                    ->update(['conversation_id' => \DB::raw($expression)]);
        } catch (\Exception $e) {
            // Fallback to row-by-row approach.
            // But it may fail on large datasets.
            $total = DatabaseNotification::whereNull('conversation_id')
                ->where('data', 'like', '%"conversation_id":%')
                ->count();
            $bunch_size = 500;
            for ($bunch_i = 0; $bunch_i < ceil($total / $bunch_size); $bunch_i++) { 
                $notifications = DatabaseNotification::whereNull('conversation_id')
                    ->where('data', 'like', '%"conversation_id":%')
                    ->orderBy('id')
                    //->skip($bunch_i*$bunch_size)
                    ->limit($bunch_size)
                    ->get();
                foreach ($notifications as $notification) {
                    if (!empty($notification->data['conversation_id']) && (int)$notification->data['conversation_id']) {
                        $notification->conversation_id = (int)$notification->data['conversation_id'];
                        $notification->save();
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('conversation_id');
            $table->dropIndex('notifications_conversation_id');
        });
    }
}
