<?php

use App\Http\Controllers\API\Farma\Productos\ProdEstadoController;
use Illuminate\Support\Facades\Route;

Route::prefix('/estados')->group(
    function (): void {
        Route::get('/listar', [ProdEstadoController::class, 'index']);
        Route::post('/crear', [ProdEstadoController::class, 'store']);
        Route::get('/buscar/{id}', [ProdEstadoController::class, 'show'])->whereNumber('id');
        Route::put('/actualizar/{id}', [ProdEstadoController::class, 'update'])->whereNumber('id');
        Route::delete('/eliminar/{id}', [ProdEstadoController::class, 'destroy'])->whereNumber('id');
    }
);
