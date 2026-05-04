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
        Schema::table('ai_memories', function (Blueprint $table) {
            $table->unsignedBigInteger('tick')->nullable()->after('category');
            $table->index(['universe_id', 'tick']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_memories', function (Blueprint $table) {
            $table->dropIndex(['universe_id', 'tick']);
            $table->dropColumn('tick');
        });
    }
};
