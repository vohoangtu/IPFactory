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
        Schema::table('chronicles', function (Blueprint $table) {
            $table->json('raw_payload')->nullable();
            $table->text('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chronicles', function (Blueprint $table) {
            $table->dropColumn('raw_payload');
            $table->text('content')->nullable(false)->change();
        });
    }
};
