<?php

use Illuminate\Support\Facades\Route;

Route::prefix('permisos')->group(function () {

    Route::post('/crearpermiso', [App\Http\Controllers\API\PermisosController::class, 'store']);
    Route::delete('/eliminarpermiso/{id}', [App\Http\Controllers\API\PermisosController::class, 'destroy']);
    Route::get('/listarpermisos/{id}', [App\Http\Controllers\API\PermisosController::class, 'findbyuser']);

    // lista de modulos del controlador modulo
    Route::get('/listarmodulos', [App\Http\Controllers\API\PermisosController::class, 'get_moduloList']);
    // vistas por modulo
    Route::get('/listarVistas/{id}', [App\Http\Controllers\API\PermisosController::class, 'get_vistasByModulo']);
   // lista de acciones por usuario
    Route::get('/listarAcciones/{id}', [App\Http\Controllers\API\PermisosController::class, 'get_accionesByVista']);
    //tiempos de permiso
    Route::get('/tipoTiempo', [App\Http\Controllers\API\PermisosController::class, 'get_tipostiempoList']);
});
