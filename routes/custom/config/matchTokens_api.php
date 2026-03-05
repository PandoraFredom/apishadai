<?php

use App\Http\Controllers\API\MatchTokensController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::prefix('matchTokens')->group(function () {
        Route::get('/', [MatchTokensController::class, 'index'])->name('matchTokens.index');
        Route::post('/', [MatchTokensController::class, 'store'])->name('matchTokens.store');
        Route::get('/{id}', [MatchTokensController::class, 'show'])->name('matchTokens.show');
        Route::put('/{id}', [MatchTokensController::class, 'update'])->name('matchTokens.update');
        Route::delete('/{id}', [MatchTokensController::class, 'destroy'])->name('matchTokens.destroy');

        // Rutas específicas para MatchTokens
        Route::get('/user/{userId}', [MatchTokensController::class, 'getByUserId'])->name('matchTokens.getByUserId');
        Route::delete('/user/{userId}/all', [MatchTokensController::class, 'deleteByUserId'])->name('matchTokens.deleteByUserId');
    });
});
