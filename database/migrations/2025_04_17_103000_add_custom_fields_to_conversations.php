<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomFieldsToConversations extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('ip');
            $table->string('source');
            $table->string('report_type');
            $table->string('path');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('ip');
            $table->dropColumn('source');
            $table->dropColumn('report_type');
            $table->dropColumn('path');
        });
    }
}
