<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_configs', function (Blueprint $table) {
            $table->foreignId('historian_profile_id')->nullable()->after('api_key')->constrained('historian_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agent_configs', function (Blueprint $table) {
            $table->dropForeign(['historian_profile_id']);
        });
    }
};
