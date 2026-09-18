<?php

use App\Http\Controllers\API\Farma\LaboratoriosController;
use Illuminate\Support\Facades\Route;

Route::prefix('/laboratorios')->group(
    function (): void {
        Route::get('/listar', [LaboratoriosController::class, 'index']);
        Route::post('/crear', [LaboratoriosController::class, 'store']);
        Route::get('/buscar/{id}', [LaboratoriosController::class, 'show'])->whereNumber('id');
        Route::put('/actualizar/{id}', [LaboratoriosController::class, 'update'])->whereNumber('id');
        Route::delete('/eliminar/{id}', [LaboratoriosController::class, 'destroy'])->whereNumber('id');
        Route::get('/imagen/{id}', [LaboratoriosController::class, 'getImage']);
    }
);
