<?php

use Illuminate\Support\Facades\Route;

use App\Modules\Intelligence\Http\Controllers\AuthController;
use App\Modules\Intelligence\Http\Controllers\AiLogController;
use App\Modules\Intelligence\Http\Controllers\AiSettingsController;
use App\Modules\Intelligence\Http\Controllers\AiDiagnosticsController;
use App\Modules\Intelligence\Http\Controllers\AiKeyPoolController;
use App\Modules\Intelligence\Http\Controllers\AiProviderModelsController;

// Authentication (public — throttle chống brute-force / spam đăng ký)
Route::middleware('api')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

// Authentication (protected)
Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
});

// AI Configuration & Logs (GET — public)
Route::middleware('api')->group(function () {
    Route::get('ai-settings', [AiSettingsController::class, 'index']);
    Route::get('ai-settings/drivers', [AiSettingsController::class, 'drivers']);
    Route::get('ai-settings/loom-agents', [AiSettingsController::class, 'loomAgents']);

    Route::get('ai-key-pool', [AiKeyPoolController::class, 'index']);
    Route::get('ai-key-pool/{ai_key_pool}', [AiKeyPoolController::class, 'show']);

    Route::get('ai-provider-models', [AiProviderModelsController::class, 'index']);
    Route::get('ai-provider-models/{id}', [AiProviderModelsController::class, 'show']);

    Route::get('/ai-logs/stats', [AiLogController::class, 'stats']);
    Route::get('/ai-logs', [AiLogController::class, 'index']);
    Route::get('/ai-logs/{id}', [AiLogController::class, 'show']);
});

// AI Configuration & Logs (POST/PATCH/PUT/DELETE — protected)
Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::post('ai-settings/update', [AiSettingsController::class, 'update']);
    Route::post('ai-settings/sync', [AiSettingsController::class, 'sync']);
    Route::post('ai-settings/import', [AiSettingsController::class, 'import']);
    Route::post('ai-settings/import-loom-agents', [AiSettingsController::class, 'importLoomAgents']);
    Route::post('ai-settings/diagnostics', [AiDiagnosticsController::class, 'run']);

    Route::post('ai-provider-models', [AiProviderModelsController::class, 'store']);
    Route::put('ai-provider-models/{id}', [AiProviderModelsController::class, 'update']);
    Route::delete('ai-provider-models/{id}', [AiProviderModelsController::class, 'destroy']);
    Route::post('ai-provider-models/import', [AiProviderModelsController::class, 'import']);
    Route::get('ai-provider-models/export', [AiProviderModelsController::class, 'export']);

    Route::post('ai-key-pool', [AiKeyPoolController::class, 'store']);
    Route::put('ai-key-pool/{ai_key_pool}', [AiKeyPoolController::class, 'update']);
    Route::patch('ai-key-pool/{ai_key_pool}', [AiKeyPoolController::class, 'update']);
    Route::delete('ai-key-pool/{ai_key_pool}', [AiKeyPoolController::class, 'destroy']);

    Route::delete('/ai-logs/clear', [AiLogController::class, 'clear']);
});

// Internal service-to-service (NarrativeLoom → backend): cấp API key từ pool.
// Bảo vệ bằng shared secret (caller là service, không có user token cho auth:sanctum).
// Trước đây các route này CÔNG KHAI và trả về API key LLM đã giải mã (lỗ hổng P0).
Route::middleware(['api', 'loom.secret'])->group(function () {
    Route::get('ai-settings/loom-key', [AiSettingsController::class, 'loomKey']);
    Route::post('ai-settings/loom-key', [AiSettingsController::class, 'loomKey']);
});
