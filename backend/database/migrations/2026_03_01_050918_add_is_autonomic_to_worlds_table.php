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
            if (!Schema::hasColumn('worlds', 'is_autonomic')) {
                $table->boolean('is_autonomic')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            if (Schema::hasColumn('worlds', 'is_autonomic')) {
                $table->dropColumn('is_autonomic');
            }
        });
    }
};
