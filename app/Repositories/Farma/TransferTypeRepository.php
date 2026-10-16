<?php

namespace App\Repositories\Farma;

use App\Models\Farma\TransferenciaTipo;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransferTypeRepository
{
    public function paginate(int $perPage = 30, string $search = ''): LengthAwarePaginator
    {
        return TransferenciaTipo::query()
            ->when($search !== '', fn ($query) => $query->where('descripcion', 'like', '%'.$search.'%'))
            ->orderBy('descripcion')->orderBy('id')
            ->paginate(max(1, min($perPage, 100)));
    }

    public function find(int $id): ?TransferenciaTipo
    {
        return TransferenciaTipo::find($id);
    }

    public function create(string $description): TransferenciaTipo
    {
        return TransferenciaTipo::create(['descripcion' => $description]);
    }

    public function update(int $id, string $description): ?TransferenciaTipo
    {
        return DB::transaction(function () use ($id, $description): ?TransferenciaTipo {
            $record = TransferenciaTipo::whereKey($id)->lockForUpdate()->first();
            $record?->update(['descripcion' => $description]);

            return $record?->refresh();
        }, 3);
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $record = TransferenciaTipo::whereKey($id)->lockForUpdate()->first();
            if ($record === null) {
                return false;
            }
            if ($record->transferencias()->exists()) {
                throw new DomainException('El tipo está relacionado con transferencias y no se puede eliminar.');
            }

            return (bool) $record->delete();
        }, 3);
    }
}
