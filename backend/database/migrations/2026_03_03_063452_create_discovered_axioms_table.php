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
        Schema::create('discovered_axioms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universe_id')->constrained()->onDelete('cascade');
            $table->integer('tick_discovered');
            $table->string('axiom_key');
            $table->text('description');
            $table->text('hypothesized_effect');
            $table->float('confidence')->default(0.5);
            $table->string('status')->default('proposed'); // proposed, active, rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discovered_axioms');
    }
};
