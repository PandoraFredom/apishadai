<?php

use App\Http\Controllers\API\Farma\DistributionController;
use Illuminate\Support\Facades\Route;

Route::prefix('distribucion')->group(function (): void {
    Route::get('/listar', [DistributionController::class, 'index']);
    Route::get('/producto/{product}', [DistributionController::class, 'productLots'])->whereNumber('product');
    Route::put('/lote/{lot}', [DistributionController::class, 'save'])->whereNumber('lot');
});
