<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\Producto;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductService
{
    public function paginate(int $perPage = 30, ?string $search = null, ?int $laboratory = null, ?int $provider = null): LengthAwarePaginator;
    public function find(int $id): ?Producto;
    public function create(array $data, array $principiosActivos = []): Producto;
    public function update(int $id, array $data, ?array $principiosActivos = null): ?Producto;
    public function delete(int $id): bool;
    public function getImage(int $id): ?string;
    public function options(): array;
}
