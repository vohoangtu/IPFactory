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
            $table->foreignId('alignment_id')->nullable()->constrained('demiurges')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('legendary_agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('alignment_id');
        });
    }
};
