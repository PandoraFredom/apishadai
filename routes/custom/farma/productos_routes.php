<?php

use Illuminate\Support\Facades\Route;

Route::prefix('productos')->group(
    function () {
        require __DIR__ . '/productos/productos_routes.php';
        require __DIR__ . '/productos/unidades_routes.php';
        require __DIR__ . '/productos/estados_routes.php';
        require __DIR__ . '/productos/categorias_routes.php';
        require __DIR__ . '/productos/presentaciones_routes.php';
        require __DIR__ . '/productos/administraciones_routes.php';
        require __DIR__ . '/productos/familias_routes.php';
        require __DIR__ . '/productos/concentraciones_routes.php';
        require __DIR__ . '/productos/principios_activos_routes.php';
        require __DIR__ . '/productos/activos_routes.php';
    }
);
