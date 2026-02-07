<?php

use Illuminate\Support\Facades\Route;

Route::prefix('promos')->group(function () {

    /*-----------------------------PROMOCIONES MODULO----------------*/
    require __DIR__ . '/promos/promociones_api.php';
    /*-----------------------------TIKETS MODULO----------------*/
    require __DIR__ . '/promos/tikets_api.php';
    /*-----------------------------CLIENTES MODULO----------------*/
    require __DIR__ . '/promos/clientes_api.php';
});
