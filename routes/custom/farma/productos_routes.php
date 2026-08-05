<?php

use App\Http\Controllers\API\Farma\Productos\ProductActiveController;
use App\Http\Controllers\API\Farma\Productos\ProductCatalogController;
use App\Http\Controllers\API\Farma\Productos\ProductController;
use Illuminate\Support\Facades\Route;

Route::pattern(
    'catalogo',
    'unidades|estados|categorias|presentaciones|administraciones|familias|concentraciones|principios-activos',
);

Route::prefix('productos')->group(function (): void {
    Route::get('/listar', [ProductController::class, 'index']);
    Route::post('/crear', [ProductController::class, 'store']);
    Route::get('/opciones', [ProductController::class, 'options']);
    Route::get('/buscar/{id}', [ProductController::class, 'show']);
    Route::put('/actualizar', [ProductController::class, 'update']);
    Route::delete('/eliminar/{id}', [ProductController::class, 'destroy']);
    Route::get('/imagen/{id}', [ProductController::class, 'getImage']);

    Route::prefix('catalogos/{catalogo}')->group(function (): void {
        Route::get('/listar', [ProductCatalogController::class, 'index']);
        Route::post('/crear', [ProductCatalogController::class, 'store']);
        Route::get('/buscar/{id}', [ProductCatalogController::class, 'show']);
        Route::put('/actualizar', [ProductCatalogController::class, 'update']);
        Route::delete('/eliminar/{id}', [ProductCatalogController::class, 'destroy']);
    });

    Route::prefix('activos')->group(function (): void {
        Route::get('/listar', [ProductActiveController::class, 'index']);
        Route::post('/crear', [ProductActiveController::class, 'store']);
        Route::get('/buscar/{id}', [ProductActiveController::class, 'show']);
        Route::put('/actualizar', [ProductActiveController::class, 'update']);
        Route::delete('/eliminar/{id}', [ProductActiveController::class, 'destroy']);
    });
});
