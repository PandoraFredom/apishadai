<?php

use App\Http\Controllers\API\StocksController;
use Illuminate\Support\Facades\Route;

Route::prefix('stock')->group(function () {
    Route::get('/listar', [StocksController::class, 'index']);
    Route::post('/crear', [StocksController::class, 'store']);
    Route::get('/buscar/{id}', [StocksController::class, 'show']);
    Route::put('/actualizar', [StocksController::class, 'update']);
    Route::delete('/eliminar/{id}', [StocksController::class, 'destroy']);
});
