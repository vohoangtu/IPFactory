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
        Schema::table('universes', function (Blueprint $table) {
            $table->unsignedBigInteger('saga_id')->nullable()->after('id');
            $table->index('saga_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->dropIndex(['saga_id']);
            $table->dropColumn('saga_id');
        });
    }
};
