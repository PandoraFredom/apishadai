<?php

use App\Http\Controllers\API\StocksEstadoController;
use Illuminate\Support\Facades\Route;

Route::prefix('stockestado')->group(function () {
    Route::get('/listar', [StocksEstadoController::class, 'index']);
    Route::post('/crear', [StocksEstadoController::class, 'store']);
    Route::get('/buscar/{id}', [StocksEstadoController::class, 'show']);
    Route::put('/actualizar', [StocksEstadoController::class, 'update']);
    Route::delete('/eliminar/{id}', [StocksEstadoController::class, 'destroy']);
});
