<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_memories', function (Blueprint $table) {
            $table->string('embedding_model')->nullable()->after('content');
            $table->string('embedding_version')->nullable()->after('embedding_model');
            $table->string('source')->nullable()->after('embedding_version');
            $table->integer('importance')->default(0)->after('source');
            $table->timestamp('expires_at')->nullable()->after('importance');
            $table->string('content_hash', 40)->nullable()->after('expires_at');

            $table->index(['universe_id', 'category']);
            $table->index(['universe_id', 'content_hash']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_memories', function (Blueprint $table) {
            $table->dropIndex(['universe_id', 'category']);
            $table->dropIndex(['universe_id', 'content_hash']);
            $table->dropIndex(['expires_at']);

            $table->dropColumn([
                'embedding_model',
                'embedding_version',
                'source',
                'importance',
                'expires_at',
                'content_hash',
            ]);
        });
    }
};
