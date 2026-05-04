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
            $table->float('heresy_score')->default(0.0);
        });
    }

    public function down(): void
    {
        Schema::table('legendary_agents', function (Blueprint $table) {
            $table->dropColumn('heresy_score');
        });
    }
};
