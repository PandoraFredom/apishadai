<?php

use App\Http\Controllers\API\Farma\Productos\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('producto')->group(
    function (): void {

        Route::get('/listar', [ProductController::class, 'index']);
        Route::post('/crear', [ProductController::class, 'store']);
        Route::get('/buscar/{id}', [ProductController::class, 'show'])->whereNumber('id');
        Route::put('/actualizar/{id}', [ProductController::class, 'update'])->whereNumber('id');
        Route::delete('/eliminar/{id}', [ProductController::class, 'destroy'])->whereNumber('id');
        Route::get('/imagen/{id}', [ProductController::class, 'getImage']);


        Route::get('/laboratorios', [ProductController::class, 'laboratorios']);
        Route::get('/filter_laboratorios', [ProductController::class, 'filterLaboratorios']);
        Route::get('/familias', [ProductController::class, 'familias']);
        Route::get('/filter_familias', [ProductController::class, 'filterFamilias']);
        Route::get('/unidades', [ProductController::class, 'unidades']);
        Route::get('/estados', [ProductController::class, 'estados']);
        Route::get('/categorias', [ProductController::class, 'categorias']);
        Route::get('/filter_unidades', [ProductController::class, 'filterUnidades']);
    }
);
