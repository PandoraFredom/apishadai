<?php


use Illuminate\Support\Facades\Route;

Route::prefix('farma')->group(function () {
    //==================================PROVEEDORES=============================
    require __DIR__ . '/../custom/farma/proveedores_routes.php';
});
