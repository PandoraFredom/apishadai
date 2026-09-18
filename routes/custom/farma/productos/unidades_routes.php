<?php

use App\Http\Controllers\API\Farma\Productos\ProdUnidadController;
use Illuminate\Support\Facades\Route;

Route::prefix('/unidades')->group(function (): void {
    Route::get('/listar', [ProdUnidadController::class, 'index']);
    Route::post('/crear', [ProdUnidadController::class, 'store']);
    Route::get('/buscar/{id}', [ProdUnidadController::class, 'show'])->whereNumber('id');
    Route::put('/actualizar/{id}', [ProdUnidadController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [ProdUnidadController::class, 'destroy'])->whereNumber('id');
});
