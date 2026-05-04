<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Knowledge\Http\Controllers\WikiController;

Route::prefix('wiki')->group(function () {
    Route::get('{universeId}/search', [WikiController::class, 'search'])->name('wiki.search');
    Route::get('{universeId}/actor/{actorId}', [WikiController::class, 'actor'])->name('wiki.actor');
    Route::get('{universeId}/axiom/{axiomId}', [WikiController::class, 'axiom'])->name('wiki.axiom');
    Route::get('axioms', [WikiController::class, 'axioms'])->name('wiki.axioms.all');
    Route::get('resolve-identity/{actorId}', [WikiController::class, 'resolveIdentity'])->name('wiki.resolve-identity');
});
