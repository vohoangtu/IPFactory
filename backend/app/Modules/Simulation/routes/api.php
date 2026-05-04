<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('apex')->group(function () {
    Route::get('/wavefunction/{universeId}', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'projectWavefunction']);
    Route::get('/informational-mass/{universeId}', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'getInformationalMass']);
    Route::get('/mutation-chronicle/{universeId}', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'getMutationChronicle']);
    Route::get('/mutation-chronicle/{universeId}/{dslHash}', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'getMutationDetail']);
    Route::get('/meaning-seeds', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'getMeaningSeeds']);

    Route::prefix('v10/universes/{universeId}')->group(function () {
        Route::get('/state-at/{tick}', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'stateAtTick']);
        Route::get('/delta', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'compareDelta']);
        Route::get('/topology', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'getTopology']);
        Route::get('/consciousness', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'getConsciousnessField']);
        Route::get('/ascension-filters', [\App\Modules\Simulation\Http\Controllers\ApexObserverController::class, 'getAscensionStatus']);
    });

    // Bloom & Multiverse DAG visualization
    Route::get('/multiverse/bloom', [\App\Modules\Simulation\Http\Controllers\MultiverseMapController::class, 'bloom']);
    Route::get('/multiverse/resonance', [\App\Modules\Simulation\Http\Controllers\MultiverseMapController::class, 'resonance']);

    // Phase 69: Simulation Dynamic Settings (GET)
    Route::get('settings', [\App\Modules\Simulation\Http\Controllers\SimulationSettingsController::class, 'index']);
});

Route::middleware(['api', 'auth:sanctum'])->prefix('apex')->group(function () {
    // Phase 69: Simulation Dynamic Settings (POST — protected)
    Route::post('settings/update', [\App\Modules\Simulation\Http\Controllers\SimulationSettingsController::class, 'update']);
    Route::post('settings/reset', [\App\Modules\Simulation\Http\Controllers\SimulationSettingsController::class, 'reset']);
});
