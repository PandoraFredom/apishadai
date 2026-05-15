<?php

use Illuminate\Support\Facades\Route;

Route::prefix('sorteos')->group(
    function () {

        Route::get('/filter', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'filter']);
        Route::get('/filter_sorteos', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'filter_sorteos']);
        Route::get('/filter_clientes', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'filter_clientes']);
        Route::get('/filter_usuarios', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'filter_usuarios']);
        Route::get('/filter_stocks', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'filter_stocks']);

        Route::get('/list_sorteos', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'get_sorteos_list']);
        Route::get('/list_clientes', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'get_clientes_list']);
        Route::get('/list_usuarios', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'get_usuarios_list']);
        Route::get('/list_stocks', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'get_stocks_list']);


    }

);
