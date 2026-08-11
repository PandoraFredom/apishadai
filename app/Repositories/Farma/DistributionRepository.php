<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\DistributionService;
use App\Models\Farma\Distribucion;
use App\Models\Farma\Lote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DistributionRepository implements DistributionService
{
    public function __construct(
        private readonly Lote $lot,
        private readonly Distribucion $distribution,
    ) {}

    public function paginateProducts(int $perPage = 30, ?string $search = null): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $query = $this->lot->newQuery()
            ->leftJoin('distribucion', 'distribucion.lote', '=', 'lotes.id')
            ->select('lotes.producto')
            ->selectRaw('COUNT(lotes.id) AS lotes_count')
            ->selectRaw('COUNT(distribucion.id) AS lotes_configurados')
            ->selectRaw('SUM(lotes.cantidad) AS cantidad_total')
            ->selectRaw('MIN(lotes.fecha_exp) AS proxima_expiracion')
            ->with([
                'productoDetalle' => static fn ($product) => $product
                    ->select(['id', 'codigo', 'descripcion', 'laboratorio', 'familia'])
                    ->selectRaw('imagen IS NOT NULL AS tiene_imagen'),
                'productoDetalle.laboratorioDetalle:id,nombre',
                'productoDetalle.familiaDetalle:id,presentacion,administracion',
                'productoDetalle.familiaDetalle.presentacionDetalle:id,descripcion',
                'productoDetalle.familiaDetalle.administracionDetalle:id,descripcion',
            ])
            ->groupBy('lotes.producto');

        if ($search !== '') {
            $query->whereHas('productoDetalle', static function (Builder $product) use ($search): void {
                $product->where(static function (Builder $product) use ($search): void {
                    $product
                        ->where('codigo', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhereHas('laboratorioDetalle', static fn (Builder $laboratory): Builder => $laboratory->where('nombre', 'like', "%{$search}%"));
                });
            });
        }

        return $query
            ->orderBy('lotes.producto')
            ->paginate(max(1, min($perPage, 100)));
    }

    /** @return Collection<int, Lote> */
    public function productLots(int $productId): Collection
    {
        return $this->lot->newQuery()
            ->where('producto', $productId)
            ->with(['distribucionDetalle', 'compraDetalle:id,nro'])
            ->orderBy('fecha_exp')
            ->orderBy('lote')
            ->get();
    }

    /** @param array<string, mixed> $data */
    public function saveForLot(int $lotId, array $data): ?Distribucion
    {
        return DB::transaction(function () use ($lotId, $data): ?Distribucion {
            $lot = $this->lot->newQuery()->lockForUpdate()->find($lotId);

            if ($lot === null) {
                return null;
            }

            $existing = $this->distribution->newQuery()->where('lote', $lot->id)->first();
            $data['isv'] = $lot->isv !== null
                ? (bool) $lot->isv
                : (bool) ($existing?->isv ?? false);

            $distribution = $this->distribution->newQuery()->updateOrCreate(
                ['lote' => $lot->id],
                $data,
            );

            return $distribution->fresh('loteDetalle.compraDetalle');
        });
    }
}
