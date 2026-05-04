<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('ai_logs', 'model')) {
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->string('model')->nullable()->after('driver')->index();
            });
        }

        DB::table('ai_logs')
            ->select(['id', 'input', 'model'])
            ->orderBy('id')
            ->chunkById(100, function ($logs): void {
                foreach ($logs as $log) {
                    if (!empty($log->model)) {
                        continue;
                    }

                    $payload = json_decode((string) $log->input, true);

                    if (!is_array($payload)) {
                        continue;
                    }

                    $model = $payload['model'] ?? $payload['model_name'] ?? null;

                    if (!is_string($model) || trim($model) === '') {
                        continue;
                    }

                    DB::table('ai_logs')
                        ->where('id', $log->id)
                        ->update(['model' => trim($model)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ai_logs', 'model')) {
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->dropColumn('model');
            });
        }
    }
};
