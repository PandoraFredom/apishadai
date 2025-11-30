<?php

use Illuminate\Support\Facades\Route;

Route::prefix('permisos')->group(function () {
 //   Route::get('/all', [App\Http\Controllers\API\PermisosController::class, 'index']);
    Route::get('/find/{id}', [App\Http\Controllers\API\PermisosController::class, 'show']);
    Route::post('/create', [App\Http\Controllers\API\PermisosController::class, 'store']);
   // Route::put('/update/{id}', [App\Http\Controllers\API\PermisosController::class, 'update']);
    Route::delete('/delete/{id}', [App\Http\Controllers\API\PermisosController::class, 'destroy']);

    Route::get('/findbyuser/{id}', [App\Http\Controllers\API\PermisosController::class, 'findbyuser']);

    // lista de modulos del controlador modulo
    Route::get('/moduloslist', [App\Http\Controllers\API\ModulosController::class, 'index']);
    // vistas por modulo
    Route::get('/vistasbymodulo/{id}', [App\Http\Controllers\API\VistasController::class, 'findbyModule']);
   // lista de acciones por usuario
    Route::get('/accionesbyvista/{id}', [App\Http\Controllers\API\ActionsVistasController::class, 'findByVista']);
    //tiempos de permiso
    Route::get('/tipostiempo', [App\Http\Controllers\API\TipoTiempoController::class, 'index']);

});