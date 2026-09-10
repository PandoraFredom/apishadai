<?php

use App\Http\Controllers\API\Farma\Productos\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('producto')->group(function (): void {
    Route::get('/listar', [ProductController::class, 'index']);
    Route::post('/crear', [ProductController::class, 'store']);
    Route::get('/opciones', [ProductController::class, 'options']);
    Route::get('/buscar/{id}', [ProductController::class, 'show']);
    Route::put('/update/{id}', [ProductController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [ProductController::class, 'destroy']);
    Route::get('/imagen/{id}', [ProductController::class, 'getImage']);
});
