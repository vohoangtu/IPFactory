<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chronicles', function (Blueprint $table) {
            $table->uuid('world_event_id')->nullable()->after('actor_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('chronicles', function (Blueprint $table) {
            $table->dropColumn('world_event_id');
        });
    }
};
