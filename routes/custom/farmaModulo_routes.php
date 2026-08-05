<?php

use Illuminate\Support\Facades\Route;

Route::prefix('farma')->group(function () {
    // ==================================PROVEEDORES=============================
    require __DIR__.'/../custom/farma/proveedores_routes.php';

    // ==================================LABORATORIOS=============================
    require __DIR__.'/../custom/farma/laboratorios_routes.php';

    // ==================================PRODUCTOS================================
    require __DIR__.'/../custom/farma/productos_routes.php';

    // ==================================COMPRAS==================================
    require __DIR__.'/../custom/farma/compras_routes.php';

    // ==================================LOTES====================================
    require __DIR__.'/../custom/farma/lotes_routes.php';

    // ==================================DISTRIBUCION=============================
    require __DIR__.'/../custom/farma/distribucion_routes.php';
});
