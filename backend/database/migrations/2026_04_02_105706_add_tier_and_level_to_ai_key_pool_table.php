<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_key_pool', function (Blueprint $table) {
            $table->string('tier')->default('free')->after('model_group');
            $table->integer('level')->default(1)->after('tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_key_pool', function (Blueprint $table) {
            $table->dropColumn(['tier', 'level']);
        });
    }
};
