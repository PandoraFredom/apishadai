<?php

use App\Http\Controllers\API\Farma\Productos\ProdCategoriaController;
use Illuminate\Support\Facades\Route;

Route::prefix('productos/catalogos/categorias')->group(function (): void {
    Route::get('/listar', [ProdCategoriaController::class, 'index']);
    Route::post('/crear', [ProdCategoriaController::class, 'store']);
    Route::get('/buscar/{id}', [ProdCategoriaController::class, 'show']);
    Route::put('/update/{id}', [ProdCategoriaController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [ProdCategoriaController::class, 'destroy']);
});
