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
        Schema::create('ai_key_pool', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // openai, anthropic, google, openrouter, etc.
            $table->string('label')->nullable(); // Ghi chú cho key (VD: Account 1 Free)
            $table->text('key_encrypted'); // Lưu key đã mã hóa
            $table->string('model_group')->default('general'); // chat, embedding, image
            $table->boolean('is_free')->default(true);
            $table->integer('usage_count')->default(0);
            $table->string('status')->default('active'); // active, cooldown, expired
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('cooldown_until')->nullable(); // Cho Rate Limit Handling
            $table->json('metadata')->nullable(); // Lưu các thông số đặc thù của provider
            $table->timestamps();

            $table->index(['provider', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_key_pool');
    }
};
