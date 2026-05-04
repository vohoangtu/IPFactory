<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flavor_texts', function (Blueprint $table) {
            $table->id();
            $table->string('vector_key'); // e.g. epistemic_instability, entropy
            $table->decimal('min_value', 10, 6)->default(0);
            $table->decimal('max_value', 10, 6)->default(1);
            $table->text('text');
            $table->string('locale')->default('en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flavor_texts');
    }
};
