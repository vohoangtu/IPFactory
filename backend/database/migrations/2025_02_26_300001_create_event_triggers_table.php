<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // unrest, secession, war, crisis...
            $table->json('context_keys')->nullable(); // vector keys that influence selection
            $table->string('name_template'); // e.g. "Khởi nghĩa Nông dân Đòi Lương thực trong Kỷ nguyên Mạt Pháp"
            $table->text('prompt_fragment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_triggers');
    }
};
