<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('universe_id');
            $table->unsignedInteger('tick');
            $table->text('dsl');
            $table->json('sandbox_result')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();
            $table->index(['universe_id', 'tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_proposals');
    }
};
