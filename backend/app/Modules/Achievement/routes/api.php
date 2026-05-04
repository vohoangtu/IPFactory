<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Achievement\Http\Controllers\AchievementController;

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('worldos/universes/{universe}/achievements', [AchievementController::class, 'index']);
    Route::post('worldos/universes/{universe}/achievements/{achievement}/claim', [AchievementController::class, 'claim']);
    Route::post('worldos/universes/{universe}/achievements/evaluate', [AchievementController::class, 'evaluate']);
    Route::get('worldos/universes/{universe}/progression', [AchievementController::class, 'progression']);
});
