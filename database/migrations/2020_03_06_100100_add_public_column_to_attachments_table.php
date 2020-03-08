<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicColumnToAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->boolean('public')->default(false);
        });
        DB::table('attachments')->update(['public' => true]);
        
        $old_path = storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'attachment');
        $new_path = storage_path('app'.DIRECTORY_SEPARATOR.'attachment');
        
        // Move attachments.
        try {
            if (File::exists($old_path) && File::isDirectory($old_path) && !File::exists($new_path)) {
                File::move($old_path, $new_path);
            }
        } catch (\Exception $e) {
            \Log::error('Migration Error: '.$e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('public');
        });
    }
}
