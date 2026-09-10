<?php

use App\Http\Controllers\API\Farma\Productos\ProductActiveController;
use Illuminate\Support\Facades\Route;

Route::prefix('productos/activos')->group(function (): void {
    Route::get('/listar', [ProductActiveController::class, 'index']);
    Route::post('/crear', [ProductActiveController::class, 'store']);
    Route::get('/buscar/{id}', [ProductActiveController::class, 'show']);
    Route::put('/update/{id}', [ProductActiveController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [ProductActiveController::class, 'destroy']);
});
