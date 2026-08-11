<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\Compra;

interface PurchaseKardexService
{
    public function send(int $purchaseId, int $userId): ?Compra;
}
