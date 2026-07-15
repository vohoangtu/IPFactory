<?php

use Illuminate\Support\Facades\Route;
use App\Modules\WorldOS\Http\Controllers\UniverseController;
use App\Modules\WorldOS\Http\Controllers\NarrativeController;
use App\Modules\WorldOS\Http\Controllers\Api\ActorController;
use App\Modules\WorldOS\Http\Controllers\Api\CentrifugoController;
use App\Modules\WorldOS\Http\Controllers\Api\WorldController;
use App\Modules\WorldOS\Http\Controllers\Api\TimelineController;
use App\Modules\WorldOS\Http\Controllers\Api\AiConfigController;
use App\Modules\WorldOS\Http\Controllers\Api\ServiceStatusController;

Route::middleware('api')->prefix('worldos')->group(function () {
    // 0. Service Status (public)
    Route::get('service-status', ServiceStatusController::class)->name('worldos.service-status');

    // Test route (tạm thời - không cần auth). Kích hoạt LLM nên phải throttle chống cost-DoS.
    // TODO(bảo mật): xóa hoặc đưa vào auth:sanctum trước khi lên production.
    Route::post('test-weave/{id}', [TimelineController::class , 'generateChronicle'])
        ->middleware('throttle:10,1')
        ->name('worldos.test-weave');

    // 1. Core Universe Management (GET — public)
    Route::get('universes', [UniverseController::class , 'index'])->name('worldos.universes.index');
    Route::get('universes/{id}', [UniverseController::class , 'show'])->name('worldos.universes.show');
    Route::get('universes/{id}/metrics', [UniverseController::class , 'metrics'])->name('worldos.universes.metrics');
    Route::get('universes/{id}/dossier', [UniverseController::class , 'dossier'])->name('worldos.universes.dossier');
    Route::get('universes/{id}/reality-state', [UniverseController::class , 'realityState'])->name('worldos.universes.reality-state');
    Route::get('universes/{id}/snapshot', [UniverseController::class , 'snapshot'])->name('worldos.universes.snapshot');
    Route::get('universes/{id}/snapshots', [UniverseController::class , 'snapshots'])->name('worldos.universes.snapshots');
    Route::get('universes/{id}/forks', [UniverseController::class , 'forks'])->name('worldos.universes.forks');
    Route::get('universes/{id}/forks/compare', [UniverseController::class , 'compareFork'])->name('worldos.universes.forks.compare');
    Route::get('snapshots/{snapshotId}', [UniverseController::class , 'getSnapshot'])->name('worldos.snapshots.show');

    // 2. World Management (Basic Only)
    Route::get('worlds', [WorldController::class , 'index'])->name('worldos.worlds.index');
    Route::get('worlds/{id}/simulation-status', [UniverseController::class , 'status'])->name('worldos.worlds.status');

    // 3. Narrative & Chronicles (Results)
    Route::get('universes/{id}/chronicles', [NarrativeController::class , 'chronicles'])->name('worldos.universes.chronicles');
    Route::get('chronicles/{chronicle}', [NarrativeController::class , 'show'])->name('worldos.chronicles.show');
    Route::get('universes/{id}/myth-scars', [NarrativeController::class , 'mythScars'])->name('worldos.universes.myth-scars');
    Route::get('universes/{id}/artifacts', [NarrativeController::class , 'artifacts'])->name('worldos.universes.artifacts');
    Route::get('universes/{id}/history-timeline', [TimelineController::class , 'history'])->name('worldos.universes.history-timeline');
    Route::get('universes/{id}/causal-links', [TimelineController::class , 'causalLinks'])->name('worldos.universes.causal-links');

    // 4. Actors & Supreme Entities (Simulation Entities)
    Route::get('universes/{id}/actors', [ActorController::class , 'index'])->name('worldos.universes.actors');
    Route::get('actors/{id}', [ActorController::class , 'show'])->name('worldos.actors.show');
    Route::get('actors/{id}/events', [ActorController::class , 'events'])->name('worldos.actors.events');
    Route::get('actors/{id}/decisions', [ActorController::class , 'decisions'])->name('worldos.actors.decisions');

    Route::get('universes/{id}/supreme-entities', [ActorController::class , 'supremeEntities'])->name('worldos.universes.supreme-entities');

    // 5. Analytics
    Route::get('analytics/ticks', [\App\Modules\WorldOS\Http\Controllers\Api\AnalyticsController::class , 'getTickAnalytics'])->name('worldos.analytics.ticks');

    // 6. System Configuration (GET)
    Route::get('config/keys', [AiConfigController::class , 'listKeys'])->name('worldos.config.keys.list');
    Route::get('config/settings', [AiConfigController::class , 'getSettings'])->name('worldos.config.settings.get');

    // 7. Centrifugo WebSocket Token
    Route::post('centrifugo/token', [CentrifugoController::class , 'token'])->name('worldos.centrifugo.token');
});

// Webhook từ narrative-loom (internal service-to-service — không dùng auth:sanctum vì
// caller không phải user đăng nhập; bảo vệ bằng shared secret, cùng cơ chế với
// endpoint sinh đôi ở Narrative module — xem backend/app/Modules/Narrative/routes/api.php).
Route::middleware(['api', 'loom.secret'])->prefix('worldos')->group(function () {
    Route::post('narrative-loom/webhook', [TimelineController::class , 'loomWebhook'])->name('worldos.narrative-loom.webhook');
});

Route::middleware(['api', 'auth:sanctum'])->prefix('worldos')->group(function () {
    // 1. Core Universe Management (POST/PATCH/DELETE — protected)
    Route::post('universes', [UniverseController::class , 'store'])->name('worldos.universes.store');
    Route::patch('universes/{id}', [UniverseController::class , 'update'])->name('worldos.universes.update');
    Route::delete('universes/{id}', [UniverseController::class , 'destroy'])->name('worldos.universes.destroy');
    Route::post('universes/{id}/toggle-status', [UniverseController::class , 'toggleStatus'])->name('worldos.universes.toggle-status');
    Route::post('universes/{id}/snapshots', [UniverseController::class , 'createSnapshot'])->name('worldos.universes.snapshots.create');

    // 3. Narrative & Chronicles (POST — protected)
    Route::post('universes/{id}/historian/generate', [TimelineController::class , 'generateHistory'])->name('worldos.universes.historian.generate');
    Route::get('universes/{id}/chronicles/raw', [TimelineController::class , 'getChronicles'])->name('worldos.universes.chronicles.raw');

// Test route: generate-chronicle (bỏ qua auth tạm thời để test)
Route::post('universes/{id}/generate-chronicle', [TimelineController::class , 'generateChronicle'])->name('worldos.universes.generate-chronicle');

    // 4. Actors (POST — protected)
    Route::post('actors/{id}/mind-meld', [ActorController::class , 'mindMeld'])->name('worldos.actors.mind-meld');

    // 5. Simulation Logic & Control (POST — protected)
    Route::post('simulation/advance', [UniverseController::class , 'advance'])->name('worldos.simulation.advance');
    Route::post('worlds/{id}/pulse', [UniverseController::class , 'pulse'])->name('worldos.worlds.pulse');
    Route::post('universes/{id}/fork', [UniverseController::class , 'fork'])->name('worldos.universes.fork');

    // 6. System Configuration (POST/DELETE — protected)
    Route::post('config/keys', [AiConfigController::class , 'storeKey'])->name('worldos.config.keys.store');
    Route::delete('config/keys/{id}', [AiConfigController::class , 'destroyKey'])->name('worldos.config.keys.destroy');
    Route::post('config/settings', [AiConfigController::class , 'updateSetting'])->name('worldos.config.settings.update');
});
