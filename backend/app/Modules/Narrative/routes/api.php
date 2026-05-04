<?php

use App\Modules\Narrative\Http\Controllers\LoomStatusController;
use App\Modules\Narrative\Http\Controllers\LoomTaskStatusController;
use App\Modules\Narrative\Http\Controllers\NarrativeController;
use App\Modules\Narrative\Http\Controllers\LoomChronicleController;
use App\Modules\Narrative\Http\Controllers\LoomCharacterController;
use App\Modules\Narrative\Http\Controllers\LoomWorldStateController;
use App\Modules\Narrative\Http\Controllers\LoomWebhookController;
use App\Modules\Narrative\Http\Controllers\LoomProxyController;
use Illuminate\Support\Facades\Route;

// Narrative Module Specific Only
Route::get('/loom-status', [LoomStatusController::class, 'status']);
Route::get('/loom-tasks/{taskId}/status', [LoomTaskStatusController::class, 'show']);
Route::get('/universes/{universe}/omen-context', [NarrativeController::class, 'omenContext']);

// Loom Integration Routes (internal - narrative-loom calls these)
Route::get('/loom/v1/narrative/chronicles', [LoomChronicleController::class, 'index']);
Route::get('/loom/v1/narrative/characters/{character_id}', [LoomCharacterController::class, 'show']);
Route::get('/loom/v1/narrative/state-snapshot/{world_id}', [LoomWorldStateController::class, 'show']);

// Webhook — Narrative Loom calls this after pipeline_done (M4 fix)
Route::post('/narrative-loom/webhook', [LoomWebhookController::class, 'receive']);

// Loom Utility Proxy — forward to Python microservice
Route::match(['get', 'post', 'put', 'delete'], '/loom/{path}', [LoomProxyController::class, 'proxy'])
    ->where('path', '.*');

