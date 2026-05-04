<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('narrative_series', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->text('description')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('narrative_series', function (Blueprint $table) {
            $table->dropColumn(['slug', 'published_at', 'description']);
        });
    }
};
