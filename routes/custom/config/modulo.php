<?php

use Illuminate\Support\Facades\Route;


Route::prefix('modulo')->group(
    function () {
        Route::get('/listar', [App\Http\Controllers\API\ModulosController::class, 'index']);
        Route::post('/crear', [App\Http\Controllers\API\ModulosController::class, 'store']);
        Route::get('/buscar/{id}', [App\Http\Controllers\API\ModulosController::class, 'show']);
        Route::put('/actualizar/{id}', [App\Http\Controllers\API\ModulosController::class, 'update']);
        Route::delete('/eliminar/{id}', [App\Http\Controllers\API\ModulosController::class, 'destroy']);

        Route::get('/estados', [App\Http\Controllers\API\ModuloEstadoController::class, 'index']);
    }
);
