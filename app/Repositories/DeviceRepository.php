<?php

namespace App\Repositories;


use App\Interfaces\Device\DeviceRepositoryInterface;
use App\Models\Device;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use function is_array;
use function is_string;

class DeviceRepository implements DeviceRepositoryInterface
{
    public function __construct(
        protected Device $model
    ) {
    }

    public function getAll(array $relations = []): Collection
    {
        $query = $this->model->newQuery();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->get();
    }

    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->paginate($perPage);
    }

    public function findById(int $id, array $relations = []): ?Device
    {
        $query = $this->model->newQuery();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->find($id);
    }

    public function create(array $data): Device
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $device = $this->findById($id);
        if (!$device) {
            return false;
        }

        // Eliminar claves con valores null, vacío o sólo espacios
        $filtered = array_filter($data, function ($value) {
            if ($value === null) {
                return false;
            }
            //si es numero y si es 0 
            if (is_numeric($value) && $value === 0) {
                return false;
            }
            if (is_string($value) && trim($value) === '') {
                return false;
            }
            if (is_array($value) && empty($value)) {
                return false;
            }
            return true;
        });

        if (empty($filtered)) {
            // No hay datos válidos para actualizar
            return false;
        }

        return $device->update($filtered);
    }

    public function delete(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) {
            return false;
        }
        return (bool) $device->delete();
    }

    public function findByDisplayname(string $displayname, ?int $excludeId = null): ?Device
    {
        $query = $this->model->newQuery()->where('displayname', $displayname);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->first();
    }

    public function findByStock(int $stockId, array $relations = []): Collection
    {
        $query = $this->model->newQuery()->where('stock', $stockId);
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->get();
    }

    public function findByEstado(int $estadoId, array $relations = []): Collection
    {
        $query = $this->model->newQuery()->where('estado', $estadoId);
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->get();
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }
}