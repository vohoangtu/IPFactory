<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historian_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('personality')->nullable();
            $table->string('bias')->nullable();
            $table->text('writing_style')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historian_profiles');
    }
};
