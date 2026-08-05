<?php

use App\Http\Controllers\API\Farma\LotController;
use Illuminate\Support\Facades\Route;

Route::prefix('lotes')->group(function (): void {
    Route::get('/listar', [LotController::class, 'index']);
    Route::get('/producto/{product}', [LotController::class, 'productLots'])->whereNumber('product');
});
