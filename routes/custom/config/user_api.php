<?php



use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->group(function () {

    Route::get('/listar', [UserController::class, 'index']);
    Route::post('/crear', [UserController::class, 'store']);
    Route::put('/actualizar', [UserController::class, 'update']);
    Route::get('/buscar/{id}', [UserController::class, 'show']);
    Route::delete('/eliminar/{id}', [UserController::class, 'destroy']);

    //==================================ROLES==============================
    Route::get('/roles', [UserController::class, 'rolList']);

    //==================================ESTADOS==============================
    Route::get('/estados', [UserController::class, 'estadosList']);
});
