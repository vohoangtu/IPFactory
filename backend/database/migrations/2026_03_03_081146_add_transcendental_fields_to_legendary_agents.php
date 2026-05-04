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
        Schema::table('legendary_agents', function (Blueprint $table) {
            $table->boolean('is_transcendental')->default(false);
            $table->json('soul_metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('legendary_agents', function (Blueprint $table) {
            $table->dropColumn(['is_transcendental', 'soul_metadata']);
        });
    }
};
