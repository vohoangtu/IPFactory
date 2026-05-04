<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutional_entities', function (Blueprint $table) {
            $table->foreignId('civilization_id')->nullable()->after('universe_id')->constrained('civilizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('institutional_entities', function (Blueprint $table) {
            $table->dropForeign(['civilization_id']);
        });
    }
};
