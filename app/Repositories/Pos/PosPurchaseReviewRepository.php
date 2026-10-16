<?php

namespace App\Repositories\Pos;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PosPurchaseReviewRepository
{
    private function query(): Builder
    {
        return DB::table('pos_compras_centrales as c')
            ->leftJoin('proveedores as p', 'p.id', '=', 'c.supplier_id')
            ->leftJoin('stocks as s', 's.id', '=', 'c.stock_id')
            ->leftJoin('usuariospos as u', 'u.id', '=', 'c.usuario_pos_id')
            ->select('c.*', 'p.nombre as supplier', 's.descripcion as stock', 'u.nombre as operator');
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = $this->query();
        foreach (['stock_id', 'supplier_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('c.'.$field, $filters[$field]);
            }
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $field => $operator) {
            if (! empty($filters[$field])) {
                $query->where('c.datos->document_date', $operator, $filters[$field]);
            }
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'cancelled') {
                $query->whereNotNull('c.cancelado_at');
            } elseif ($filters['status'] === 'accepted') {
                $query->whereNotNull('c.compra_aceptada_uuid')->whereNull('c.cancelado_at');
            } else {
                $query->whereNull('c.compra_aceptada_uuid')->whereNull('c.cancelado_at');
            }
        }
        if (! empty($filters['product_id'])) {
            if (DB::getDriverName() === 'sqlite') {
                $query->whereRaw("EXISTS(SELECT 1 FROM json_each(c.datos, '$.lines') j WHERE json_extract(j.value, '$.product_id') = ?)", [$filters['product_id']]);
            } else {
                $query->whereRaw("JSON_SEARCH(c.datos, 'one', ?, NULL, '$.lines[*].product_id') IS NOT NULL", [$filters['product_id']]);
            }
        }
        $page = $query->orderByDesc('c.created_at')->orderByDesc('c.request_id')->paginate(30);
        $page->setCollection($page->getCollection()->map(fn ($row) => $this->document($row, false)));

        return $page;
    }

    public function find(string $request): ?array
    {
        $row = $this->query()->where('c.request_id', $request)->first();

        return $row ? $this->document($row, true) : null;
    }

    public function options(string $kind, string $search): array
    {
        [$table, $name] = match ($kind) {
            'stocks' => ['stocks', 'descripcion'], 'suppliers' => ['proveedores', 'nombre'],
            'products' => ['productos', 'descripcion'],
        };
        $query = DB::table($table)->select('id', $name.' as name');
        if ($kind === 'products') {
            $query->addSelect('codigo as code');
        }
        if ($search !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function ($query) use ($name, $pattern, $kind) {
                $query->whereRaw($name." LIKE ? ESCAPE '!'", [$pattern]);
                if ($kind === 'products') {
                    $query->orWhereRaw("codigo LIKE ? ESCAPE '!'", [$pattern])->orWhereRaw("codigobar LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }

        return $query->orderBy($name)->orderBy('id')->limit(30)->get()->map(function ($row) {
            $result = (array) $row;
            $result['id'] = (string) $row->id;

            return $result;
        })->all();
    }

    private function rounded(string $value): string
    {
        return bcadd(bcadd($value, '0.005', 8), '0', 2);
    }

    private function document(object $row, bool $detail): array
    {
        $payload = json_decode($row->datos, true, flags: JSON_THROW_ON_ERROR);
        $response = json_decode($row->respuesta, true, flags: JSON_THROW_ON_ERROR);
        $subtotal = '0.00';
        $tax = '0.00';
        $lines = [];
        $products = $detail ? DB::table('productos')->whereIn('id', array_column($payload['lines'], 'product_id'))
            ->select('id', 'codigo', 'descripcion')->get()->keyBy('id') : collect();
        foreach ($payload['lines'] as $line) {
            $net = $this->rounded(bcmul($line['quantity'], $line['cost'], 8));
            $isv = $line['has_tax'] ? $this->rounded(bcmul($net, '0.15', 8)) : '0.00';
            $subtotal = bcadd($subtotal, $net, 2);
            $tax = bcadd($tax, $isv, 2);
            if ($detail) {
                $product = $products->get($line['product_id']);
                $lines[] = [
                    'line_id' => $line['line_id'], 'product_id' => $line['product_id'],
                    'code' => $product?->codigo, 'description' => $product?->descripcion,
                    'quantity' => $line['quantity'], 'purchase_factor' => $line['purchase_factor'],
                    'base_quantity' => $line['base_quantity'], 'base_cost' => $line['base_cost'],
                    'lot' => $line['lot'], 'manufactured' => $line['manufactured'], 'expires' => $line['expires'],
                    'cost' => $line['cost'], 'has_tax' => $line['has_tax'],
                    'subtotal' => $net, 'tax' => $isv, 'total' => bcadd($net, $isv, 2),
                ];
            }
        }

        return [
            'id' => $row->request_id, 'purchase_uuid' => $row->purchase_uuid,
            'stock_id' => (string) $row->stock_id, 'stock' => $row->stock,
            'supplier' => $row->supplier, 'operator' => $row->operator,
            'invoice_cai' => $payload['invoice_cai'], 'date' => $payload['document_date'],
            'status' => $row->cancelado_at ? 'cancelled' : ($response['accepted'] ? 'accepted' : 'rejected'),
            'status_label' => $row->cancelado_at ? 'Cancelada' : ($response['accepted'] ? 'Aceptada' : 'Rechazada'),
            'reason' => $row->cancelado_at ? 'Compra eliminada en POS antes de recibir inventario.' : ($response['accepted'] ? null : match ($response['code']) {
                'SIN_ACCESO' => 'El operador no tenía permiso para recibir.',
                'COMPRA_RECIBIDA' => 'La factura del proveedor ya fue recibida.',
                'PROVEEDOR_AUSENTE' => 'Proveedor no disponible.',
                'UNIDAD_AUSENTE', 'UNIDAD_CAMBIO' => 'Revisa la unidad de compra del producto.',
                'LOTE_AMBIGUO' => 'Hay varios lotes que coinciden con el producto y sus fechas.',
                default => 'El detalle enviado requiere revisión.',
            }),
            'cancelled_at' => $row->cancelado_at,
            'registered_at' => $row->created_at, 'subtotal' => $subtotal,
            'tax' => $tax, 'total' => bcadd($subtotal, $tax, 2),
            ...($detail ? ['equipment' => $payload['equipment'], 'lines' => $lines] : []),
        ];
    }
}
