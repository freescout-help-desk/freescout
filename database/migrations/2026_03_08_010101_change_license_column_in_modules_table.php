<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLIcenseColumnInModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->text('license')->nullable()->change();
        });
        foreach (\App\Module::get() as $module) {
            $license = $module->getOriginal('license');
            if ($license) {
                $module->setLicenseAttribute($license);
                $module->save();
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

    }
}
