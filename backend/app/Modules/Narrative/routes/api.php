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

// Webhook — Narrative Loom calls this after pipeline_done.
// Bảo vệ bằng shared secret: trước đây CÔNG KHAI → attacker tiêm final_prose/headline
// vào Chronicle của world bất kỳ rồi broadcast cho mọi client (lỗ hổng P0).
Route::post('/narrative-loom/webhook', [LoomWebhookController::class, 'receive'])
    ->middleware('loom.secret');

// Loom Utility Proxy — forward request của user tới NarrativeLoom (kích hoạt LLM/asset tốn phí).
// Yêu cầu đăng nhập + throttle; regex chặn path traversal (loại '.'); chỉ GET/POST.
// Trước đây mở cho mọi verb, không auth, path '.*' → ẩn danh điều khiển service nội bộ (P0).
Route::match(['get', 'post'], '/loom/{path}', [LoomProxyController::class, 'proxy'])
    ->where('path', '[A-Za-z0-9\-_/]+')
    ->middleware(['auth:sanctum', 'throttle:30,1']);

