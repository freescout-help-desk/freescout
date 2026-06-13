<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetUserOnlyAssignedPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $show_only_assigned_conversations = config('app.show_only_assigned_conversations') ?? '';
        $users_ids = explode(',', $show_only_assigned_conversations);

        foreach ($users_ids as $user_id) {
            $user_id = trim($user_id);
            if (!$user_id) {
                continue;
            }
            $user_id = (int)$user_id;

            $user = User::find($user_id);
            if (!$user) {
                continue;
            }

            $user->addPermission(User::PERM_ONLY_ASSIGNED_TICKETS);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
