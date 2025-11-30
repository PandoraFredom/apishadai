<?php

namespace App\Interfaces\Device;

use App\Models\Device;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DeviceRepositoryInterface
{
    public function getAll(array $relations = []): Collection;
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator;
    public function findById(int $id, array $relations = []): ?Device;
    public function create(array $data): Device;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function findByDisplayname(string $displayname, ?int $excludeId = null): ?Device;
    public function findByStock(int $stockId, array $relations = []): Collection;
    public function findByEstado(int $estadoId, array $relations = []): Collection;
    public function exists(int $id): bool;

}