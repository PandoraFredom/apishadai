<?php

use Illuminate\Support\Facades\Route;

Route::prefix('sorteos')->group(
    function () {

        /*-----------------------------Sorteos MODULO----------------*/
        require __DIR__ . '/sorteos/promociones_api.php';
        /*-----------------------------TIKETS MODULO----------------*/
        require __DIR__ . '/sorteos/tikets_api.php';
        /*-----------------------------CLIENTES MODULO----------------*/
        require __DIR__ . '/sorteos/clientes_api.php';
    }

);
