<?php

namespace App\Services\Farma;

use App\Models\Stocks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferStockScope
{
    public function canFilter(Request $request): bool
    {
        $userId = $request->user()?->getAuthIdentifier();

        if ($userId === null) {
            return false;
        }

        return DB::table('permisos')
            ->join('modulos', 'permisos.modulo', '=', 'modulos.id')
            ->join('vistas', 'permisos.vista', '=', 'vistas.id')
            ->join('actionsvistas', 'permisos.actionvista', '=', 'actionsvistas.id')
            ->where('permisos.usuario', $userId)
            ->where('modulos.codigo', 'frm')
            ->where('vistas.codigo', 'trf')
            ->whereColumn('vistas.modulo', 'modulos.id')
            ->whereColumn('actionsvistas.vista', 'vistas.id')
            ->where('actionsvistas.codigo', 'trfbtn07')
            ->where(fn ($query) => $query->whereNull('permisos.lifetime')->orWhere('permisos.lifetime', '>=', now()))
            ->exists();
    }

    public function stockId(Request $request, ?int $currentStockId): ?int
    {
        if ($currentStockId === null) {
            return null;
        }

        $validated = $request->validate(['stock_id' => ['nullable', 'integer', 'min:1']]);

        if (! isset($validated['stock_id'])) {
            return $currentStockId;
        }

        abort_unless($this->canFilter($request), 403, 'No tienes permiso para filtrar transferencias por stock.');
        $stock = $this->stocksQuery()->find((int) $validated['stock_id']);
        abort_if($stock === null, 422, 'El stock seleccionado no está activo o no existe.');

        return (int) $stock->id;
    }

    /** @return array<int, array{id: int, descripcion: string}> */
    public function stocks(): array
    {
        return $this->stocksQuery()->orderBy('descripcion')->get(['id', 'descripcion'])
            ->map(fn (Stocks $stock): array => [
                'id' => (int) $stock->id,
                'descripcion' => (string) $stock->descripcion,
            ])->all();
    }

    private function stocksQuery(): Builder
    {
        return Stocks::query()->whereHas('Estado', fn (Builder $status): Builder => $status->where('descripcion', 'ACTIVO'));
    }
}
