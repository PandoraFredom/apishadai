<?php
namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use function is_array;
use function is_string;

abstract class Repository implements RepositoryInterface
{
    protected Model $model;
    protected array $defaultRelations = [];
    protected int $perPage = 15;

    protected string $order = 'DESC';

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getAll(): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($this->defaultRelations)) {
            $query->with($this->defaultRelations);
        }

        return $query->orderBy('id', $this->order)->get();
    }

    public function paginate(): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($this->defaultRelations)) {
            $query->with($this->defaultRelations);
        }

        return $query->orderBy('id', $this->order)->paginate($this->perPage);
    }

    public function findById(int $id): ?Model
    {
        $query = $this->model->newQuery();

        if (!empty($this->defaultRelations)) {
            $query->with($this->defaultRelations);
        }

        return $query->find($id);
    }

    public function create(array $data): bool
    {
        try {
            DB::transaction(function () use ($data) {
                $this->model->create($data);
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            DB::transaction(function () use ($id, $data) {
                $model = $this->findById($id);

                if (!$model) {
                    throw new \Exception("Model not found");
                }

                $filtered = $this->filterEmptyData($data);

                if (empty($filtered)) {
                    throw new \Exception("No data to update");
                }

                if (!$model->update($filtered)) {
                    throw new \Exception("Update failed");
                }
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            DB::transaction(function () use ($id) {
                $model = $this->findById($id);

                if (!$model) {
                    throw new \Exception("Model not found");
                }

                if (!$model->delete()) {
                    throw new \Exception("Delete failed");
                }
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function filterEmptyData(array $data): array
    {
        return array_filter($data, function ($value) {
            if ($value === null) {
                return false;
            }

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
    }
}