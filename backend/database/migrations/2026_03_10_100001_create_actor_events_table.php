<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actor life timeline: events per actor (chronicle at actor level).
     * Enables "life timeline" UI and culture/history trace.
     */
    public function up(): void
    {
        Schema::create('actor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('tick');
            $table->string('event_type', 64);
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['actor_id', 'tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_events');
    }
};
