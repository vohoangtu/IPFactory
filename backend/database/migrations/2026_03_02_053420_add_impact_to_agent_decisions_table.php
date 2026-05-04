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
        Schema::table('agent_decisions', function (Blueprint $table) {
            $table->json('impact')->nullable()->after('utility_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_decisions', function (Blueprint $table) {
            $table->dropColumn('impact');
        });
    }
};
