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
        Schema::table('worlds', function (Blueprint $table) {
            $table->string('current_genre')->default('wuxia')->after('origin');
            $table->string('base_genre')->default('wuxia')->after('current_genre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropColumn(['current_genre', 'base_genre']);
        });
    }
};
