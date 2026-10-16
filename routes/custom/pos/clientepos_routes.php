<?php

use App\Http\Controllers\API\Pos\ClienteposController;
use App\Http\Middleware\EnsureClienteposPermission;
use Illuminate\Support\Facades\Route;

Route::prefix('pos/clientepos')->group(function (): void {
    Route::get('listar', [ClienteposController::class, 'index'])->middleware(EnsureClienteposPermission::class.':clpbtn01');
    Route::post('crear', [ClienteposController::class, 'store'])->middleware(EnsureClienteposPermission::class.':clpbtn02');
    Route::get('buscar/{id}', [ClienteposController::class, 'show'])->whereNumber('id')->middleware(EnsureClienteposPermission::class.':clpbtn03');
    Route::put('actualizar/{id}', [ClienteposController::class, 'update'])->whereNumber('id')->middleware(EnsureClienteposPermission::class.':clpbtn04');
    Route::delete('eliminar/{id}', [ClienteposController::class, 'destroy'])->whereNumber('id')->middleware(EnsureClienteposPermission::class.':clpbtn05');
});
