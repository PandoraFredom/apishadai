<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\Compra;
use Illuminate\Pagination\LengthAwarePaginator;

interface PurchaseService
{
    public function paginate(int $perPage = 30, string $search = ''): LengthAwarePaginator;
    public function find(int $id): ?Compra;
    public function create(array $header, array $details): Compra;
    public function createDraft(array $header): Compra;
    public function syncHeader(int $id, array $header): ?Compra;
    public function syncDetail(int $purchaseId, array $detail): ?Compra;
    public function deleteDetail(int $purchaseId, int $detailId): ?Compra;
    public function finalize(int $id): ?Compra;
    public function update(int $id, array $header, array $details): ?Compra;
    public function delete(int $id): bool;
    public function document(int $id): ?string;
    public function providerImage(int $id): ?string;
    public function options(): array;
}
