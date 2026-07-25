<?php

use App\Http\Controllers\API\Farma\ProveedoresController;
use Illuminate\Support\Facades\Route;

Route::prefix('proveedores')->group(function () {
    Route::get('/listar', [ProveedoresController::class, 'index']);
    Route::post('/crear', [ProveedoresController::class, 'store']);
    Route::get('/buscar/{id}', [ProveedoresController::class, 'show']);
    Route::put('/actualizar', [ProveedoresController::class, 'update']);
    Route::delete('/eliminar/{id}', [ProveedoresController::class, 'destroy']);

    Route::get('/imagen/{id}', [ProveedoresController::class, 'getImage']);
});
