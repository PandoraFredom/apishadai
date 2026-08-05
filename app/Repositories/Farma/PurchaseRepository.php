<?php

namespace App\Repositories\Farma;

use App\DTOs\Farma\PurchaseData;
use App\Models\Farma\Compra;
use App\Models\Farma\CompraDetalle;
use App\Models\Farma\CompraTipo;
use App\Models\Farma\Producto;
use App\Models\Proveedores;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseRepository
{
    private const RELATIONS = [
        'tipoDetalle',
        'proveedorDetalle:id,nombre',
        'usuarioDetalle:id,nombre,name',
        'kardexUsuarioDetalle:id,nombre,name',
        'detalles.productoDetalle.unidadDetalle',
        'detalles.productoDetalle.familiaDetalle.presentacionDetalle',
        'detalles.productoDetalle.laboratorioDetalle',
        'transacciones:id,compra,valor',
    ];

    public function __construct(private readonly Compra $model) {}

    public function paginate(int $perPage = 30, string $search = ''): LengthAwarePaginator
    {
        $query = $this->baseQuery();
        $search = trim($search);

        if ($search !== '') {
            $like = "%{$search}%";

            $query->where(function (Builder $filter) use ($search, $like): void {
                $filter->where('compras.nro', 'like', $like)
                    ->orWhere('compras.estado', 'like', $like)
                    ->orWhere('compras.nota', 'like', $like)
                    ->orWhereHas('proveedorDetalle', fn (Builder $provider): Builder => $provider->where('nombre', 'like', $like))
                    ->orWhereHas('tipoDetalle', fn (Builder $type): Builder => $type->where('descripcion', 'like', $like))
                    ->orWhereHas('usuarioDetalle', fn (Builder $user): Builder => $user
                        ->where('nombre', 'like', $like)
                        ->orWhere('name', 'like', $like))
                    ->orWhereHas('detalles', fn (Builder $detail): Builder => $detail->where('lote', 'like', $like))
                    ->orWhereHas('detalles.productoDetalle', fn (Builder $product): Builder => $product->where('descripcion', 'like', $like));

                if (is_numeric($search)) {
                    $amount = (float) $search;
                    $filter->orWhere('compras.subtotal', $amount)
                        ->orWhere('compras.isv', $amount)
                        ->orWhere('compras.descuento', $amount)
                        ->orWhere('compras.total', $amount)
                        ->orWhereRaw(
                            '(compras.total - COALESCE((SELECT SUM(compra_transc.valor) FROM compra_transc WHERE compra_transc.compra = compras.id), 0)) = ?',
                            [$amount],
                        );
                }
            });
        }

        return $query->orderByDesc('id')->paginate(max(1, min($perPage, 100)));
    }

    public function find(int $id): ?Compra
    {
        return $this->baseQuery()->find($id);
    }

    /** @param array<string, mixed> $header @param array<int, array<string, mixed>> $details */
    public function create(array $header, array $details): Compra
    {
        return DB::transaction(function () use ($header, $details): Compra {
            $purchase = $this->model->newQuery()->create($header);
            $purchase->detalles()->createMany($details);

            return $this->find((int) $purchase->getKey()) ?? $purchase;
        });
    }

    /** @param array<string, mixed> $header */
    public function createDraft(array $header): Compra
    {
        return $this->create([
            ...$header,
            'items' => 0,
            'isv' => 0,
            'subtotal' => 0,
            'descuento' => 0,
            'total' => 0,
            'estado' => 'borrador',
        ], []);
    }

    /** @param array<string, mixed> $header */
    public function syncHeader(int $id, array $header): ?Compra
    {
        return DB::transaction(function () use ($id, $header): ?Compra {
            $purchase = $this->editablePurchase($id);

            if ($purchase === null) {
                return null;
            }

            $purchase->update($header);

            return $this->find($id);
        });
    }

    /** @param array<string, mixed> $detail */
    public function syncDetail(int $purchaseId, array $detail): ?Compra
    {
        return DB::transaction(function () use ($purchaseId, $detail): ?Compra {
            $purchase = $this->editablePurchase($purchaseId);

            if ($purchase === null) {
                return null;
            }

            $detailId = (int) ($detail['id'] ?? 0);
            unset($detail['id']);
            $line = PurchaseData::lineTotals($detail);
            $detail['total'] = $line['total'];

            if ($detailId > 0) {
                $record = $purchase->detalles()->whereKey($detailId)->lockForUpdate()->first();

                if ($record === null) {
                    throw new DomainException('El producto no pertenece a esta compra.');
                }

                $record->update($detail);
            } else {
                $purchase->detalles()->create($detail);
            }

            $this->refreshTotals($purchase);

            return $this->find($purchaseId);
        });
    }

    public function deleteDetail(int $purchaseId, int $detailId): ?Compra
    {
        return DB::transaction(function () use ($purchaseId, $detailId): ?Compra {
            $purchase = $this->editablePurchase($purchaseId);

            if ($purchase === null) {
                return null;
            }

            $detail = $purchase->detalles()->whereKey($detailId)->lockForUpdate()->first();

            if ($detail === null) {
                throw new DomainException('El producto no pertenece a esta compra.');
            }

            $detail->delete();
            $this->refreshTotals($purchase);

            return $this->find($purchaseId);
        });
    }

    public function finalize(int $id): ?Compra
    {
        return DB::transaction(function () use ($id): ?Compra {
            $purchase = $this->model->newQuery()->with('detalles')->lockForUpdate()->find($id);

            if ($purchase === null) {
                return null;
            }

            if ($purchase->detalles->isEmpty()) {
                throw new DomainException('Agrega al menos un producto antes de finalizar la compra.');
            }

            foreach ($purchase->detalles as $detail) {
                if ((float) $detail->costo <= 0 || trim((string) $detail->lote) === ''
                    || $detail->fecha_elaboracion === null || $detail->fecha_expiracion === null) {
                    throw new DomainException('Todos los productos deben tener lote, fechas de elaboración y expiración, y un costo mayor que cero.');
                }

                if ($detail->fecha_expiracion->lt($detail->fecha_elaboracion)) {
                    throw new DomainException('La fecha de expiración no puede ser anterior a la fecha de elaboración.');
                }

                $line = PurchaseData::lineTotals($detail->toArray());

                if ((float) $line['descuento'] > ((float) $line['subtotal'] + (float) $line['isv'])) {
                    throw new DomainException('El descuento de un producto no puede superar su subtotal más ISV.');
                }
            }

            $this->refreshTotals($purchase);
            $purchase->update(['estado' => 'pendiente']);

            return $this->find($id);
        });
    }

    /** @param array<string, mixed> $header @param array<int, array<string, mixed>> $details */
    public function update(int $id, array $header, array $details): ?Compra
    {
        return DB::transaction(function () use ($id, $header, $details): ?Compra {
            $purchase = $this->model->newQuery()->lockForUpdate()->find($id);

            if ($purchase === null) {
                return null;
            }

            if ($purchase->kardex_enviado_at !== null) {
                throw new DomainException('Una compra enviada al Kardex no puede modificarse.');
            }

            $purchase->update($header);
            $purchase->detalles()->delete();
            $purchase->detalles()->createMany($details);

            return $this->find($id);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $purchase = $this->model->newQuery()->lockForUpdate()->find($id);

            if ($purchase === null || $purchase->kardex_enviado_at !== null || $purchase->transacciones()->exists()) {
                return false;
            }

            $purchase->detalles()->delete();

            return (bool) $purchase->delete();
        });
    }

    public function document(int $id): ?string
    {
        $value = $this->model->newQuery()->whereKey($id)->value('img');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function providerImage(int $id): ?string
    {
        $value = Proveedores::query()->whereKey($id)->value('imagen');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function options(): array
    {
        return [
            'tipos' => CompraTipo::query()->orderBy('descripcion')->get()->map(fn (CompraTipo $type): array => [
                'id' => (int) $type->getKey(), 'label' => (string) $type->descripcion,
            ])->all(),
            'proveedores' => Proveedores::query()
                ->select(['id', 'nombre'])
                ->selectRaw('imagen IS NOT NULL AS tiene_imagen')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Proveedores $provider): array => [
                    'id' => (int) $provider->id,
                    'label' => (string) $provider->nombre,
                    'tiene_imagen' => (bool) $provider->tiene_imagen,
                ])->all(),
            'productos' => Producto::query()
                ->with(['unidadDetalle', 'familiaDetalle.presentacionDetalle', 'laboratorioDetalle'])
                ->orderBy('descripcion')
                ->get(['id', 'laboratorio', 'unidad', 'familia', 'descripcion'])
                ->map(fn (Producto $product): array => [
                    'id' => (int) $product->id,
                    'label' => (string) $product->descripcion,
                    'descripcion' => (string) $product->descripcion,
                    'laboratorio' => (string) ($product->laboratorioDetalle?->nombre ?? ''),
                    'presentacion' => (string) ($product->familiaDetalle?->presentacionDetalle?->descripcion ?? ''),
                    'unidad_compra' => (int) ($product->unidadDetalle?->cantidad_c ?? 0),
                ])->all(),
        ];
    }

    private function baseQuery(): Builder
    {
        return $this->model->newQuery()
            ->select(['id', 'tipo', 'proveedor', 'usuario', 'plazo', 'nro', 'items', 'isv', 'subtotal', 'descuento', 'total', 'estado', 'nota', 'kardex_enviado_at', 'kardex_usuario', 'created_at', 'updated_at'])
            ->selectRaw('img IS NOT NULL AS tiene_documento')
            ->with(self::RELATIONS)
            ->withSum('transacciones as total_aplicado', 'valor');
    }

    private function editablePurchase(int $id): ?Compra
    {
        $purchase = $this->model->newQuery()->lockForUpdate()->find($id);

        if ($purchase?->estado === 'pagada') {
            throw new DomainException('Una compra pagada no puede modificarse.');
        }

        if ($purchase?->kardex_enviado_at !== null) {
            throw new DomainException('Una compra enviada al Kardex no puede modificarse.');
        }

        return $purchase;
    }

    private function refreshTotals(Compra $purchase): void
    {
        $totals = ['items' => 0, 'subtotal' => 0.0, 'isv' => 0.0, 'descuento' => 0.0, 'total' => 0.0];

        $purchase->detalles()->get()->each(function (CompraDetalle $detail) use (&$totals): void {
            $line = PurchaseData::lineTotals($detail->toArray());
            $totals['items'] += (int) $detail->cantidad;
            $totals['subtotal'] += (float) $line['subtotal'];
            $totals['isv'] += (float) $line['isv'];
            $totals['descuento'] += (float) $line['descuento'];
            $totals['total'] += (float) $line['total'];
        });

        $purchase->update([
            'items' => $totals['items'],
            'subtotal' => round($totals['subtotal'], 2),
            'isv' => round($totals['isv'], 2),
            'descuento' => round($totals['descuento'], 2),
            'total' => round($totals['total'], 2),
        ]);
    }
}
