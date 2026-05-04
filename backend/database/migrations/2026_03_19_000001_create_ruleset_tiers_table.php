<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruleset_tiers', function (Blueprint $table) {
            $table->smallInteger('tier')->primary();
            $table->string('label', 50);
            $table->text('description')->nullable();
            $table->text('ontology')->nullable();
            $table->string('entity_ceiling', 100)->nullable();
            $table->jsonb('examples')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruleset_tiers');
    }
};
