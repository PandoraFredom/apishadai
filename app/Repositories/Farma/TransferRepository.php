<?php

namespace App\Repositories\Farma;

use App\DTOs\Farma\PurchaseData;
use App\Models\Farma\Lote;
use App\Models\Farma\Transferencia;
use App\Models\Farma\TransferenciaEstado;
use App\Models\Farma\TransferenciaTipo;
use App\Models\Stocks;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransferRepository
{
    private const RELATIONS = [
        'tipoDetalle:id,descripcion',
        'stockOrigen:id,descripcion',
        'stockDestino:id,descripcion',
        'usuarioEnvia:id,nombre,name',
        'usuarioRecibe:id,nombre,name',
        'estadoDetalle:id,descripcion',
        'estadoRecepcionDetalle:id,descripcion',
        'detalles.loteDetalle.productoDetalle:id,codigo,descripcion',
    ];

    public function __construct(private readonly Transferencia $model) {}

    /** @param array<string, mixed> $filters */
    public function paginate(int $stockId, int $perPage = 30, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQuery()->where(function (Builder $participant) use ($stockId): void {
            $participant->where('stock_de', $stockId)->orWhere('stock_para', $stockId);
        });

        $direction = (string) ($filters['direccion'] ?? 'todas');

        if ($direction === 'enviadas') {
            $query->where('stock_de', $stockId);
        } elseif ($direction === 'recibidas') {
            $query->where('stock_para', $stockId);
        }

        if (! empty($filters['estado'])) {
            $query->where('estado', (int) $filters['estado']);
        }

        if (! empty($filters['estado_recepcion'])) {
            $query->where('estado_recepcion', (int) $filters['estado_recepcion']);
        }

        if (! empty($filters['tipo'])) {
            $query->where('tipo', (int) $filters['tipo']);
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function (Builder $searchQuery) use ($search, $like): void {
                if (ctype_digit($search)) {
                    $searchQuery->orWhereKey((int) $search);
                }

                $searchQuery
                    ->orWhereHas('stockOrigen', fn (Builder $stock): Builder => $stock->where('descripcion', 'like', $like))
                    ->orWhereHas('stockDestino', fn (Builder $stock): Builder => $stock->where('descripcion', 'like', $like))
                    ->orWhereHas('usuarioEnvia', fn (Builder $user): Builder => $user->where('nombre', 'like', $like))
                    ->orWhereHas('usuarioRecibe', fn (Builder $user): Builder => $user->where('nombre', 'like', $like))
                    ->orWhereHas('detalles.loteDetalle', fn (Builder $lot): Builder => $lot->where('lote', 'like', $like))
                    ->orWhereHas('detalles.loteDetalle.productoDetalle', fn (Builder $product): Builder => $product
                        ->where('codigo', 'like', $like)
                        ->orWhere('descripcion', 'like', $like));
            });
        }

        return $query->orderByDesc('id')->paginate(max(1, min($perPage, 100)));
    }

    public function findForStock(int $id, int $stockId): ?Transferencia
    {
        return $this->baseQuery()
            ->whereKey($id)
            ->where(fn (Builder $participant): Builder => $participant
                ->where('stock_de', $stockId)
                ->orWhere('stock_para', $stockId))
            ->first();
    }

    /** @param array<string, mixed> $data */
    public function send(int $originStockId, int $userId, array $data): Transferencia
    {
        return DB::transaction(function () use ($originStockId, $userId, $data): Transferencia {
            $destinationStockId = (int) $data['stock_para'];

            if ($originStockId === $destinationStockId) {
                throw new DomainException('El stock de destino debe ser diferente al stock de origen.');
            }

            $destination = Stocks::query()->with('Estado')->lockForUpdate()->find($destinationStockId);

            if ($destination === null || mb_strtoupper((string) ($destination->Estado?->descripcion ?? '')) !== 'ACTIVO') {
                throw new DomainException('El stock de destino no está activo.');
            }

            $sentStatusId = $this->statusId('ENVIADA');
            $pendingStatusId = $this->statusId('PENDIENTE');
            $details = collect($data['detalles'] ?? []);
            $rows = [];
            $items = 0;
            $subtotal = 0.0;
            $tax = 0.0;
            $total = 0.0;

            if ($details->isNotEmpty()) {
                $lots = Lote::query()->whereKey($details->pluck('lote')->map(fn ($id): int => (int) $id)->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($lots->count() !== $details->count()) {
                    throw new DomainException('Uno o más lotes ya no están disponibles.');
                }

                foreach ($details as $detail) {
                    $lot = $lots->get((int) $detail['lote']);
                    $quantity = (int) $detail['cantidad'];

                    if ($quantity > (int) $lot->cantidad) {
                        throw new DomainException("La cantidad solicitada supera la existencia del lote {$lot->lote}.");
                    }

                    if ($lot->costo === null) {
                        throw new DomainException("El lote {$lot->lote} no tiene costo registrado.");
                    }

                    $line = PurchaseData::lineTotals([
                        'cantidad' => $quantity,
                        'costo' => (float) $lot->costo,
                        'isv' => (bool) $lot->isv,
                        'descuento' => 0,
                    ]);
                    $rows[] = [
                        'lote' => (int) $lot->id,
                        'cantidad' => $quantity,
                        'subtotal' => $line['subtotal'],
                        'isv' => $line['isv'],
                        'total' => $line['total'],
                    ];
                    $items += $quantity;
                    $subtotal += (float) $line['subtotal'];
                    $tax += (float) $line['isv'];
                    $total += (float) $line['total'];
                }
            }

            $transfer = $this->model->newQuery()->create([
                'tipo' => (int) $data['tipo'],
                'stock_de' => $originStockId,
                'stock_para' => $destinationStockId,
                'usuario_envia' => $userId,
                'usuario_recibe' => null,
                'items' => $items,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'isvtotal' => number_format($tax, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'estado' => $sentStatusId,
                'estado_recepcion' => $pendingStatusId,
                'enviado_at' => now(),
                'recibido_at' => null,
            ]);

            if ($rows !== []) {
                $transfer->detalles()->createMany($rows);
            }

            return $this->findForStock((int) $transfer->id, $originStockId) ?? $transfer;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateDetails(int $id, int $originStockId, array $data): Transferencia
    {
        return DB::transaction(function () use ($id, $originStockId, $data): Transferencia {
            $transfer = $this->model->newQuery()->whereKey($id)->where('stock_de', $originStockId)->lockForUpdate()->first();

            if ($transfer === null) {
                throw new DomainException('Transferencia no encontrada o no pertenece a este stock.');
            }

            $pendingStatusId = $this->statusId('PENDIENTE');
            if ((int) $transfer->estado_recepcion !== $pendingStatusId || $transfer->usuario_recibe !== null) {
                throw new DomainException('No se puede modificar una transferencia que ya fue recibida.');
            }

            $details = collect($data['detalles'] ?? []);
            $rows = [];
            $items = 0;
            $subtotal = 0.0;
            $tax = 0.0;
            $total = 0.0;

            if ($details->isNotEmpty()) {
                $lots = Lote::query()->whereKey($details->pluck('lote')->map(fn ($lId): int => (int) $lId)->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($lots->count() !== $details->count()) {
                    throw new DomainException('Uno o más lotes seleccionados ya no existen.');
                }

                foreach ($details as $detail) {
                    $lot = $lots->get((int) $detail['lote']);
                    $quantity = (int) $detail['cantidad'];

                    if ($quantity > (int) $lot->cantidad) {
                        throw new DomainException("La cantidad solicitada supera la existencia del lote {$lot->lote}.");
                    }

                    if ($lot->costo === null) {
                        throw new DomainException("El lote {$lot->lote} no tiene costo registrado.");
                    }

                    $line = PurchaseData::lineTotals([
                        'cantidad' => $quantity,
                        'costo' => (float) $lot->costo,
                        'isv' => (bool) $lot->isv,
                        'descuento' => 0,
                    ]);
                    $rows[] = [
                        'lote' => (int) $lot->id,
                        'cantidad' => $quantity,
                        'subtotal' => $line['subtotal'],
                        'isv' => $line['isv'],
                        'total' => $line['total'],
                    ];
                    $items += $quantity;
                    $subtotal += (float) $line['subtotal'];
                    $tax += (float) $line['isv'];
                    $total += (float) $line['total'];
                }
            }

            $transfer->detalles()->delete();
            if ($rows !== []) {
                $transfer->detalles()->createMany($rows);
            }

            $transfer->update([
                'items' => $items,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'isvtotal' => number_format($tax, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
            ]);

            return $this->findForStock($id, $originStockId) ?? $transfer;
        }, 3);
    }

    public function receive(int $id, int $destinationStockId, int $userId): ?Transferencia
    {
        return DB::transaction(function () use ($id, $destinationStockId, $userId): ?Transferencia {
            $transfer = $this->model->newQuery()
                ->with(['estadoDetalle', 'estadoRecepcionDetalle'])
                ->lockForUpdate()
                ->find($id);

            if ($transfer === null) {
                return null;
            }

            if ((int) $transfer->stock_para !== $destinationStockId) {
                throw new DomainException('La transferencia solo puede recibirse desde el stock de destino.');
            }

            if ((int) $transfer->usuario_envia === $userId) {
                throw new DomainException('La transferencia debe ser recibida por un usuario diferente al que la envió.');
            }

            if (mb_strtoupper((string) ($transfer->estadoDetalle?->descripcion ?? '')) !== 'ENVIADA'
                || mb_strtoupper((string) ($transfer->estadoRecepcionDetalle?->descripcion ?? '')) !== 'PENDIENTE'
                || $transfer->usuario_recibe !== null) {
                throw new DomainException('La transferencia ya fue recibida o no se encuentra pendiente de recepción.');
            }

            $transfer->update([
                'usuario_recibe' => $userId,
                'estado_recepcion' => $this->statusId('RECIBIDA'),
                'recibido_at' => now(),
            ]);

            return $this->findForStock($id, $destinationStockId);
        }, 3);
    }

    /** @return array<string, mixed> */
    public function options(int $stockId): array
    {
        return [
            'stock_actual' => Stocks::query()->find($stockId, ['id', 'descripcion']),
            'stocks_destino' => Stocks::query()
                ->whereKeyNot($stockId)
                ->whereHas('Estado', fn (Builder $state): Builder => $state->whereRaw('UPPER(descripcion) = ?', ['ACTIVO']))
                ->orderBy('descripcion')
                ->get(['id', 'descripcion']),
            'tipos' => TransferenciaTipo::query()->orderBy('descripcion')->get(['id', 'descripcion']),
            'estados' => TransferenciaEstado::query()->orderBy('id')->get(['id', 'descripcion']),
            'estados_envio' => TransferenciaEstado::query()
                ->whereRaw('UPPER(descripcion) = ?', ['ENVIADA'])
                ->get(['id', 'descripcion']),
            'estados_recepcion' => TransferenciaEstado::query()
                ->whereIn(DB::raw('UPPER(descripcion)'), ['PENDIENTE', 'RECIBIDA'])
                ->orderBy('id')
                ->get(['id', 'descripcion']),
        ];
    }

    public function paginateLots(int $perPage = 30, string $search = '', ?int $productId = null): LengthAwarePaginator
    {
        $search = trim($search);
        $query = Lote::query()
            ->where('cantidad', '>', 0)
            ->whereNotNull('costo')
            ->with(['productoDetalle:id,codigo,descripcion,laboratorio', 'productoDetalle.laboratorioDetalle:id,nombre']);

        if ($productId !== null && $productId > 0) {
            $query->where('producto', $productId);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(fn (Builder $filter): Builder => $filter
                ->where('lote', 'like', $like)
                ->orWhereHas('productoDetalle', fn (Builder $product): Builder => $product
                    ->where('codigo', 'like', $like)
                    ->orWhere('descripcion', 'like', $like)));
        }

        return $query->orderBy('fecha_exp')->orderBy('lote')->paginate(max(1, min($perPage, 100)));
    }

    private function baseQuery(): Builder
    {
        return $this->model->newQuery()->with(self::RELATIONS);
    }

    private function statusId(string $description): int
    {
        $id = TransferenciaEstado::query()
            ->whereRaw('UPPER(descripcion) = ?', [mb_strtoupper($description)])
            ->value('id');

        if ($id === null) {
            throw new DomainException("No está configurado el estado de transferencia {$description}.");
        }

        return (int) $id;
    }
}
