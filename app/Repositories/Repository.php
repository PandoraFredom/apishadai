<?php
namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function is_array;
use function is_string;

abstract class Repository implements RepositoryInterface
{
    protected Model $model;
    protected array $defaultRelations = [];
    protected int $perPage = 15;

    protected  array $orderBy = ["id","desc"];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getAll(): Collection
    {
        try {
            $query = $this->model->newQuery();

            if (!empty($this->defaultRelations)) {
                $query->with($this->defaultRelations);
            }

            return $query->orderByBy($this->orderBy[0], $this->orderBy[1])->get();
        } catch (\Exception $e) {
            Log::error('Repository::getAll - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return collect();
        }
    }

    public function paginate(): LengthAwarePaginator
    {
        try {
            $query = $this->model->newQuery();

            if (!empty($this->defaultRelations)) {
                $query->with($this->defaultRelations);
            }

            return $query->orderByBy($this->orderBy[0], $this->orderBy[1])->paginate($this->perPage);
        } catch (\Exception $e) {
            Log::error('Repository::paginate - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'perPage' => $this->perPage,
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->model->paginate($this->perPage);
        }
    }

    public function findById(int $id): ?Model
    {
        try {
            $query = $this->model->newQuery();

            if (!empty($this->defaultRelations)) {
                $query->with($this->defaultRelations);
            }

            return $query->find($id);
        } catch (\Exception $e) {
            Log::error('Repository::findById - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'id' => $id,
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    public function create(array $data): bool
    {
        try {
            DB::transaction(function () use ($data) {
                $this->model->create($data);
            });
            return true;
        } catch (\Exception $e) {
            Log::error('Repository::create - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'data' => $data,
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            DB::transaction(function () use ($id, $data) {
                $model = $this->findById($id);

                if (!$model) {
                    throw new \Exception("Model not found with id: $id");
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
            Log::error('Repository::update - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'id' => $id,
                'data' => $data,
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            DB::transaction(function () use ($id) {
                $model = $this->findById($id);

                if (!$model) {
                    throw new \Exception("Model not found with id: $id");
                }

                if (!$model->delete()) {
                    throw new \Exception("Delete failed");
                }
            });
            return true;
        } catch (\Exception $e) {
            Log::error('Repository::delete - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'id' => $id,
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return false;
        }
    }

    protected function filterEmptyData(array $data): array
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Repository::filterEmptyData - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [];
        }
    }
}