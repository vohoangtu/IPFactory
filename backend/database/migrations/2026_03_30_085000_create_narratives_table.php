<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('narratives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('universe_id')->index();
            $table->bigInteger('tick_born')->index();
            $table->text('story');
            $table->float('virality')->default(1.0);
            $table->float('distortion')->default(0.0);
            $table->json('field_effects')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('source_event_id')->nullable();
            $table->string('news_headline', 255)->nullable();
            $table->string('news_slogan', 255)->nullable();
            $table->json('vfx_config')->nullable();
            $table->timestamps();

            $table->foreign('universe_id')->references('id')->on('universes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('narratives');
    }
};
