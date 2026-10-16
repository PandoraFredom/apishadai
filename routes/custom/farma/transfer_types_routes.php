<?php

use App\Http\Controllers\API\Farma\TransferTypeController;
use App\Http\Middleware\EnsureTransferTypePermission;
use Illuminate\Support\Facades\Route;

Route::prefix('transferencias/tipos')->group(function (): void {
    Route::get('/listar', [TransferTypeController::class, 'index'])->middleware(EnsureTransferTypePermission::class.':tftbtn01');
    Route::post('/crear', [TransferTypeController::class, 'store'])->middleware(EnsureTransferTypePermission::class.':tftbtn02');
    Route::get('/buscar/{id}', [TransferTypeController::class, 'show'])->whereNumber('id')->middleware(EnsureTransferTypePermission::class.':tftbtn03');
    Route::put('/actualizar', [TransferTypeController::class, 'update'])->middleware(EnsureTransferTypePermission::class.':tftbtn04');
    Route::delete('/eliminar/{id}', [TransferTypeController::class, 'destroy'])->whereNumber('id')->middleware(EnsureTransferTypePermission::class.':tftbtn05');
});
