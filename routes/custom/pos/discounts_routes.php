<?php

use App\Http\Controllers\API\Pos\PosDiscountController;
use App\Http\Middleware\EnsurePosManagementPermission;
use Illuminate\Support\Facades\Route;

Route::prefix('pos/descuentos')->group(function (): void {
    foreach (['listar' => ['get', 'index', 1], 'crear' => ['post', 'store', 2], 'buscar/{id}' => ['get', 'show', 3],
        'actualizar/{id}' => ['put', 'update', 4], 'desactivar/{id}' => ['delete', 'destroy', 5],
        'eliminar/{id}' => ['delete', 'delete', 6],
        'opciones' => ['get', 'choices', 2]] as $path => [$method, $handler, $number]) {
        Route::$method($path, [PosDiscountController::class, $handler])->whereNumber('id')
            ->middleware(EnsurePosManagementPermission::class.':dcp,dcpbtn0'.$number);
    }
});
