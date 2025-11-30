<?php


use App\Http\Controllers\API\RolesController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserEstadosController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->group(function () {

    Route::get('/cargarUsuarios', [UserController::class, 'index']);
    Route::post('/crearUsuario', [AuthController::class, 'register']);
    Route::put('/actualizarUsuario', [AuthController::class, 'update']);
    Route::delete('/eliminarUsuario/{id}', [UserController::class, 'destroy']);
    Route::get('/buscarUsuario/{id}', [UserController::class, 'show']);
    Route::get('/findbyname', [UserController::class, 'findLikeNombre']);

    //==================================ROLES==============================
    Route::get('/usuarioRoles', [RolesController::class, 'index']);

    //==================================ESTADOS==============================
    Route::get('/estadoUsuario', [UserEstadosController::class, 'index']);
});
