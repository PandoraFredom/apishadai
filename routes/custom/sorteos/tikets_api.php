<?php


use Illuminate\Support\Facades\Route;

Route::prefix( 'ticket')->group(function () {
    //rutas tikets
    Route::get('/all', [App\Http\Controllers\API\tiketsController::class, 'index']);
    Route::post('/create', [App\Http\Controllers\API\tiketsController::class, 'store']);
    Route::get('/sorteoactual', [App\Http\Controllers\API\tiketsController::class, 'getpromo']);

    //rutas clientes
    Route::get('/clientes', [App\Http\Controllers\API\tiketsController::class, 'get_clientesList']);
    Route::get('/buscarcliente', [App\Http\Controllers\API\tiketsController::class, 'filter_clientes']);
    Route::post('/creacliente', [App\Http\Controllers\API\tiketsController::class, 'create_cliente']);
    Route::post('/updatephone', [App\Http\Controllers\API\tiketsController::class, 'update_phone_cliente']);
    Route::get('/activephone/{id}', [App\Http\Controllers\API\tiketsController::class, 'activephone']);

    //listar departamento y municicipio
    Route::get('/departamentos', [App\Http\Controllers\API\tiketsController::class, 'get_departamentosList']);
    Route::get('/municipios/{id}', [App\Http\Controllers\API\tiketsController::class, 'get_municipiosList']);
});
