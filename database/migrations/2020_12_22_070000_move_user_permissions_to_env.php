<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveUserPermissionsToEnv extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = Option::get('user_permissions');

        if (!empty($permissions)) {
            $permissions_encoded = base64_encode(json_encode($permissions));
            \Helper::setEnvFileVar('APP_USER_PERMISSIONS', $permissions_encoded);
            config('user_permissions', $permissions_encoded);
            \Helper::clearCache(['--doNotGenerateVars' => true]);
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
