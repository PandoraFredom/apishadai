<?php

use App\Http\Controllers\API\Pos\PosPurchaseReviewController;
use App\Http\Middleware\EnsurePosManagementPermission;
use Illuminate\Support\Facades\Route;

Route::prefix('pos/compras')->group(function (): void {
    Route::get('listar', [PosPurchaseReviewController::class, 'index'])->middleware(EnsurePosManagementPermission::class.':cpp,cppbtn01');
    Route::get('filtrar', [PosPurchaseReviewController::class, 'index'])->defaults('filtered', true)->middleware(EnsurePosManagementPermission::class.':cpp,cppbtn02');
    Route::get('opciones/{kind}', [PosPurchaseReviewController::class, 'options'])->whereIn('kind', ['stocks', 'suppliers', 'products'])->middleware(EnsurePosManagementPermission::class.':cpp,cppbtn02');
    Route::get('buscar/{id}', [PosPurchaseReviewController::class, 'show'])->whereUuid('id')->middleware(EnsurePosManagementPermission::class.':cpp,cppbtn03');
});
