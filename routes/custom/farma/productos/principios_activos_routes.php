<?php

use App\Http\Controllers\API\Farma\Productos\PrincipalActivoController;
use Illuminate\Support\Facades\Route;

Route::prefix('productos/catalogos/principios-activos')->group(function (): void {
    Route::get('/listar', [PrincipalActivoController::class, 'index']);
    Route::post('/crear', [PrincipalActivoController::class, 'store']);
    Route::get('/buscar/{id}', [PrincipalActivoController::class, 'show'])->whereNumber('id');
    Route::put('/update/{id}', [PrincipalActivoController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [PrincipalActivoController::class, 'destroy'])->whereNumber('id');
});
