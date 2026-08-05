<?php

use App\Http\Controllers\API\Farma\Compras\PurchaseCatalogController;
use App\Http\Controllers\API\Farma\Compras\PurchaseController;
use App\Http\Controllers\API\Farma\Compras\PurchaseTransactionController;
use Illuminate\Support\Facades\Route;

Route::pattern('catalogoCompra', 'tipos-compra|tipos-transaccion');

Route::prefix('compras')->group(function (): void {
    Route::get('/listar', [PurchaseController::class, 'index']);
    Route::post('/crear', [PurchaseController::class, 'store']);
    Route::post('/borrador', [PurchaseController::class, 'storeDraft']);
    Route::patch('/borrador/{id}', [PurchaseController::class, 'syncDraft']);
    Route::put('/borrador/{purchase}/detalle', [PurchaseController::class, 'syncDetail']);
    Route::delete('/borrador/{purchase}/detalle/{detail}', [PurchaseController::class, 'deleteDetail']);
    Route::post('/finalizar/{id}', [PurchaseController::class, 'finalize']);
    Route::get('/opciones', [PurchaseController::class, 'options']);
    Route::get('/proveedor-imagen/{id}', [PurchaseController::class, 'providerImage']);
    Route::get('/reporte/{id}', [PurchaseController::class, 'report']);
    Route::post('/kardex/{id}', [PurchaseController::class, 'sendToKardex']);
    Route::get('/buscar/{id}', [PurchaseController::class, 'show']);
    Route::put('/actualizar', [PurchaseController::class, 'update']);
    Route::delete('/eliminar/{id}', [PurchaseController::class, 'destroy']);
    Route::get('/documento/{id}', [PurchaseController::class, 'document']);

    Route::prefix('transacciones')->group(function (): void {
        Route::get('/listar', [PurchaseTransactionController::class, 'index']);
        Route::post('/crear', [PurchaseTransactionController::class, 'store']);
        Route::get('/opciones', [PurchaseTransactionController::class, 'options']);
        Route::get('/buscar/{id}', [PurchaseTransactionController::class, 'show']);
        Route::put('/actualizar', [PurchaseTransactionController::class, 'update']);
        Route::delete('/eliminar/{id}', [PurchaseTransactionController::class, 'destroy']);
        Route::get('/documento/{id}', [PurchaseTransactionController::class, 'document']);
    });

    Route::prefix('catalogos/{catalogoCompra}')->group(function (): void {
        Route::get('/listar', [PurchaseCatalogController::class, 'index']);
        Route::post('/crear', [PurchaseCatalogController::class, 'store']);
        Route::get('/buscar/{id}', [PurchaseCatalogController::class, 'show']);
        Route::put('/actualizar', [PurchaseCatalogController::class, 'update']);
        Route::delete('/eliminar/{id}', [PurchaseCatalogController::class, 'destroy']);
    });
});
