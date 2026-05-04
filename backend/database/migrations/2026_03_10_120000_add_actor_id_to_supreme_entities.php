<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Option B: link SupremeEntity (Great Person) to Actor so they appear in Personae.
     */
    public function up(): void
    {
        Schema::table('supreme_entities', function (Blueprint $table) {
            $table->foreignId('actor_id')->nullable()->after('universe_id')->constrained('actors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supreme_entities', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
        });
    }
};
