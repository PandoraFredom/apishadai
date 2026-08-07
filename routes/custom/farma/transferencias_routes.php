<?php

use App\Http\Controllers\API\Farma\TransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('transferencias')->group(function (): void {
    Route::get('/listar', [TransferController::class, 'index']);
    Route::get('/opciones', [TransferController::class, 'options']);
    Route::get('/lotes', [TransferController::class, 'lots']);
    Route::post('/enviar', [TransferController::class, 'send']);
    Route::post('/actualizar/{id}', [TransferController::class, 'update'])->whereNumber('id');
    Route::post('/recibir/{id}', [TransferController::class, 'receive'])->whereNumber('id');
    Route::get('/buscar/{id}', [TransferController::class, 'show'])->whereNumber('id');
});
