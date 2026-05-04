<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->string('primary_ruleset_id')->default('realistic_modern')->after('origin');
            $table->foreign('primary_ruleset_id')->references('id')->on('ruleset_definitions');
        });
    }

    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropForeign(['primary_ruleset_id']);
            $table->dropColumn('primary_ruleset_id');
        });
    }
};
