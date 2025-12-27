<?php

use Illuminate\Support\Facades\Route;

Route::prefix('permisos')->group(function () {

    Route::post('/create', [App\Http\Controllers\API\PermisosController::class, 'store']);
    Route::delete('/delete/{id}', [App\Http\Controllers\API\PermisosController::class, 'destroy']);
    Route::get('/findbyuser/{id}', [App\Http\Controllers\API\PermisosController::class, 'findbyuser']);

    // lista de modulos del controlador modulo
    Route::get('/moduloslist', [App\Http\Controllers\API\PermisosController::class, 'get_moduloList']);
    // vistas por modulo
    Route::get('/vistasbymodulo/{id}', [App\Http\Controllers\API\PermisosController::class, 'get_vistasByModulo']);
   // lista de acciones por usuario
    Route::get('/accionesbyvista/{id}', [App\Http\Controllers\API\PermisosController::class, 'get_accionesByVista']);
    //tiempos de permiso
    Route::get('/tipostiempo', [App\Http\Controllers\API\PermisosController::class, 'get_tipostiempoList']);
});
