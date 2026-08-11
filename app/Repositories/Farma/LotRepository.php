<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\LotService;
use App\Models\Farma\Lote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LotRepository implements LotService
{
    public function __construct(private readonly Lote $model) {}

    public function paginateProducts(int $perPage = 30, ?string $search = null): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $query = $this->model->newQuery()
            ->select('producto')
            ->selectRaw('COUNT(*) AS lotes_count')
            ->selectRaw('SUM(cantidad) AS cantidad_total')
            ->selectRaw('MIN(fecha_exp) AS proxima_expiracion')
            ->with([
                'productoDetalle' => static fn ($product) => $product
                    ->select(['id', 'codigo', 'descripcion', 'laboratorio', 'familia'])
                    ->selectRaw('imagen IS NOT NULL AS tiene_imagen'),
                'productoDetalle.laboratorioDetalle:id,nombre',
                'productoDetalle.familiaDetalle:id,presentacion,administracion',
                'productoDetalle.familiaDetalle.presentacionDetalle:id,descripcion',
                'productoDetalle.familiaDetalle.administracionDetalle:id,descripcion',
            ])
            ->groupBy('producto');

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
            ->orderBy('producto')
            ->paginate(max(1, min($perPage, 100)));
    }

    /** @return Collection<int, Lote> */
    public function productLots(int $productId): Collection
    {
        return $this->model->newQuery()
            ->where('producto', $productId)
            ->with([
                'productoDetalle:id,codigo,descripcion,laboratorio',
                'productoDetalle.laboratorioDetalle:id,nombre',
                'compraDetalle:id,nro',
            ])
            ->orderBy('fecha_exp')
            ->orderBy('lote')
            ->get();
    }
}
