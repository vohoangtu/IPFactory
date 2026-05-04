<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->uuid('policy_id')->nullable()->after('id');
            $table->uuid('arena_batch_id')->nullable()->after('policy_id');
            $table->bigInteger('random_seed')->default(0)->after('arena_batch_id');
            $table->enum('arena_status', ['pending', 'running', 'completed'])->default('pending')->after('random_seed');

            $table->index('arena_batch_id');
            $table->index('arena_status');
        });
    }

    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->dropColumn(['policy_id', 'arena_batch_id', 'random_seed', 'arena_status']);
        });
    }
};
