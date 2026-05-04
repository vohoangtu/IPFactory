<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->json('kernel_genome')->nullable()->after('state_vector');
            $table->float('fitness_score')->default(0.0)->after('kernel_genome');
        });
    }

    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->dropColumn(['kernel_genome', 'fitness_score']);
        });
    }
};
