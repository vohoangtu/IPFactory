<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Grace period: universe con (fork) không bị archive cho đến khi chạy đủ N tick kể từ forked_at_tick.
     */
    public function up(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->unsignedBigInteger('forked_at_tick')->nullable()->after('parent_universe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->dropColumn('forked_at_tick');
        });
    }
};
