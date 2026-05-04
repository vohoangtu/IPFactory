<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chronicles', function (Blueprint $table) {
            $table->json('animation_script')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('chronicles', function (Blueprint $table) {
            $table->dropColumn('animation_script');
        });
    }
};
