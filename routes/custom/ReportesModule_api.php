<?php

use Illuminate\Support\Facades\Route;

Route::prefix('reportes')->group(
    function () {

        /*-----------------------------Sorteos----------------*/
        require __DIR__ . '/reportes/sorteos_api.php';

    }

);
