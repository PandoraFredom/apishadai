<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\tiketsController as ctrl;


Route::prefix('ticket')->group(function () {
    //rutas tikets
    Route::get('/all', [ctrl::class, 'index']);
    Route::post('/create', [ctrl::class, 'store']);
    Route::get('/sorteoactual', [ctrl::class, 'getpromo']);

    //rutas clientes
    Route::get('/clientes', [ctrl::class, 'get_clientesList']);
    Route::get('/buscarcliente', [ctrl::class, 'filter_clientes']);
    Route::post('/creacliente', [ctrl::class, 'create_cliente']);
    Route::post('/updatephone', [ctrl::class, 'update_phone_cliente']);
    Route::get('/activephone/{id}', [ctrl::class, 'activephone']);

    //listar departamento y municicipio
    Route::get('/departamentos', [ctrl::class, 'get_departamentosList']);
    Route::get('/municipios/{id}', [ctrl::class, 'get_municipiosList']);
});
