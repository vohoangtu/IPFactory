<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->json('engine_manifest')->nullable()->after('state_vector');
        });
    }

    public function down(): void
    {
        Schema::table('universes', function (Blueprint $table) {
            $table->dropColumn('engine_manifest');
        });
    }
};
