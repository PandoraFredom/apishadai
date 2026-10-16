<?php

use App\Http\Controllers\API\Pos\PosManagementController;
use App\Http\Controllers\API\Pos\PosUserPermissionController;
use App\Http\Middleware\EnsurePosManagementPermission;
use Illuminate\Support\Facades\Route;

foreach (['usuarios' => 'usp', 'roles' => 'rsp', 'catalogos' => 'cop'] as $kind => $view) {
    Route::prefix('pos/'.$kind)->group(function () use ($kind, $view): void {
        foreach (['listar' => ['get', 'index', 1], 'crear' => ['post', 'store', 2], 'buscar/{id}' => ['get', 'show', 3],
            'actualizar/{id}' => ['put', 'update', 4], 'desactivar/{id}' => ['delete', 'destroy', 5]] as $path => [$method, $handler, $number]) {
            Route::$method($path, [PosManagementController::class, $handler])->defaults('resource', $kind)->whereNumber('id')
                ->middleware(EnsurePosManagementPermission::class.':'.$view.','.$view.'btn0'.$number);
        }
    });
}

Route::prefix('pos/permisos')->group(function (): void {
    $controller = PosUserPermissionController::class;
    foreach (['listarpermisos/{id}' => 'index', 'spermisos/{id}' => 'grouped', 'listarmodulos' => 'modules', 'listarVistas/{id}' => 'views', 'listarAcciones/{id}' => 'actions', 'tipoTiempo' => 'times'] as $path => $handler) {
        Route::get($path, [$controller, $handler])->whereNumber('id')->middleware(EnsurePosManagementPermission::class.':usp,uspbtn03');
    }
    Route::post('crearpermiso', [$controller, 'store'])->middleware(EnsurePosManagementPermission::class.':usp,uspbtn04');
    Route::delete('eliminarpermiso/{id}', [$controller, 'destroy'])->whereNumber('id')->middleware(EnsurePosManagementPermission::class.':usp,uspbtn04');
});
