<?php

use Illuminate\Support\Facades\Route;

Route::prefix('reportes')->group(
    function () {

        /*-----------------------------Sorteos----------------*/
        require __DIR__ . '/reportes/rpt_sorteos_route.php';

        /*--------------------------Reporte WorkLunch----------*/
        require __DIR__ . '/reportes/rpt_worklunch_route.php';

    }

);
