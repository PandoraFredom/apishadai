<?php

namespace App\Repositories\Pos;

use App\DTO\ClienteposDTO;
use App\Interfaces\Pos\ClienteposService;
use App\Models\Pos\Clientepos;
use App\Repositories\Repository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClienteposRepository extends Repository implements ClienteposService
{
    public function __construct(Clientepos $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
    }

    public function paginate(string $search = ''): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        $search = trim($search);

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                foreach (['nombre', 'rtn', 'telefono', 'direccion'] as $field) {
                    $query->orWhere($field, 'like', '%'.$search.'%');
                }
            });
        }

        return $query->orderByDesc('id')->paginate($this->perPage);
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data): bool {
            $client = $this->model->newQuery()->lockForUpdate()->find($id);

            if ($client === null) {
                return false;
            }
            $sanitized = $this->sanitizeData($data);
            $previous = ClienteposDTO::normalize(['rtn' => $client->rtn]);
            if ($client->tipo_documento === null && $sanitized['tipo_documento'] === null && $sanitized['rtn'] === $previous['rtn']) {
                $sanitized['rtn'] = $client->rtn;
            }

            return $client->update($sanitized);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $client = $this->model->newQuery()->lockForUpdate()->find($id);

            return $client !== null && (bool) $client->delete();
        });
    }

    protected function sanitizeData(array $data): array
    {
        return ClienteposDTO::fromRequest($data)->toArray();
    }
}
