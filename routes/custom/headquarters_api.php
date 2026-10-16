<?php

use App\Http\Controllers\API\Headquarters\PosProductLotController;
use App\Http\Controllers\API\Headquarters\PosPurchaseController;
use App\Http\Controllers\API\Headquarters\PosTransferController;
use App\Http\Controllers\API\Headquarters\RegistrationController;
use App\Http\Controllers\WorkLunchController;
use App\Http\Middleware\DeviceSecurityMiddleware;
use App\Http\Middleware\HeadquartersPosSignatureMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('headquarters')->withoutMiddleware([DeviceSecurityMiddleware::class])->group(function (): void {
    Route::post('pos/transferencias/{action}', [PosTransferController::class, 'handle'])
        ->whereIn('action', ['enviar', 'recibir', 'anular', 'confirmar-anulacion', 'listar'])
        ->middleware(HeadquartersPosSignatureMiddleware::class);
    Route::post('pos/productos/lotes-hijos/{action}', [PosProductLotController::class, 'handle'])
        ->whereIn('action', ['preparar', 'consultar'])->middleware(HeadquartersPosSignatureMiddleware::class);
    Route::post('pos/compras/{action}', [PosPurchaseController::class, 'handle'])
        ->whereIn('action', ['recibir', 'consultar', 'cancelar'])->middleware(HeadquartersPosSignatureMiddleware::class);
    Route::post('pos/asistencia/{action}', [WorkLunchController::class, 'headquarters'])
        ->whereIn('action', ['registrar', 'listar'])->middleware(HeadquartersPosSignatureMiddleware::class);
    Route::post('registro/solicitar', [RegistrationController::class, 'requestRegistration'])->middleware('throttle:headquarters-registration');
    Route::post('registro/confirmar', [RegistrationController::class, 'confirmRegistration'])->middleware('throttle:headquarters-confirmation');
});
