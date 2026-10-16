<?php

namespace App\Services\Headquarters;

use App\Models\Device;
use App\Models\Farma\Lote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PosPurchaseService
{
    public function execute(int $deviceId, string $clientUuid, string $action, array $payload): array
    {
        abort_unless(extension_loaded('bcmath'), 503);

        return DB::transaction(function () use ($deviceId, $clientUuid, $action, $payload): array {
            $device = Device::with(['Estado', 'Stock.Estado'])->lockForUpdate()->find($deviceId);
            abort_unless($device && $device->hq_client_uuid === $clientUuid
                && strtoupper((string) $device->Estado?->descripcion) === 'ACTIVO'
                && strtoupper((string) $device->Stock?->Estado?->descripcion) === 'ACTIVO', 401);
            $stock = (string) $device->stock;
            DB::table('stocks')->where('id', $stock)->lockForUpdate()->first();
            if ($action === 'consultar') {
                Validator::make($payload, ['request_id' => ['required', 'uuid']])->validate();
                $row = DB::table('pos_compras_centrales')->where('request_id', $payload['request_id'])->where('stock_id', $stock)->first();
                abort_unless($row, 404);

                return json_decode($row->respuesta, true, 512, JSON_THROW_ON_ERROR);
            }
            if ($action === 'cancelar') {
                return $this->cancel($deviceId, $stock, $payload);
            }
            abort_unless($action === 'recibir', 404);

            return $this->receive($deviceId, $stock, $payload);
        }, 3);
    }

    private function receive(int $deviceId, string $stock, array $payload): array
    {
        $decimal = ['required', 'string', 'regex:/\A[0-9]{1,15}(\.[0-9]{1,4})?\z/'];
        $id = ['required', 'string', 'regex:/\A[1-9][0-9]{0,19}\z/'];
        Validator::make($payload, [
            'version' => ['required', 'integer', 'in:1'], 'request_id' => ['required', 'uuid'],
            'purchase_uuid' => ['required', 'uuid'], 'stock_id' => $id, 'usuario_pos_id' => $id,
            'supplier_id' => $id, 'equipment' => ['required', 'string', 'max:128'],
            'invoice_cai' => ['required', 'string', 'max:100'], 'document_date' => ['required', 'date_format:Y-m-d'],
            'lines' => ['required', 'array', 'min:1', 'max:1000'],
            'lines.*.line_id' => ['required', 'uuid', 'distinct'], 'lines.*.product_id' => $id,
            'lines.*.lot' => ['required', 'string', 'max:100'],
            'lines.*.manufactured' => ['required', 'date_format:Y-m-d'], 'lines.*.expires' => ['required', 'date_format:Y-m-d'],
            'lines.*.quantity' => $decimal, 'lines.*.purchase_factor' => $decimal,
            'lines.*.base_quantity' => $decimal, 'lines.*.cost' => $decimal, 'lines.*.base_cost' => $decimal,
            'lines.*.has_tax' => ['required', 'boolean'],
        ])->validate();
        abort_unless($payload['stock_id'] === $stock, 403);
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $json);
        $existing = DB::table('pos_compras_centrales')->where('request_id', $payload['request_id'])->lockForUpdate()->first();
        $cancelled = DB::table('pos_compra_cancelaciones')->where('receipt_request_id', $payload['request_id'])->lockForUpdate()->first();
        if ($cancelled) {
            abort_unless((string) $cancelled->stock_id === $stock && (int) $cancelled->device_id === $deviceId && $cancelled->purchase_uuid === $payload['purchase_uuid']
                && (! $existing || hash_equals($existing->huella, $hash)), 409);

            return ['version' => 1, 'request_id' => $payload['request_id'], 'purchase_uuid' => $payload['purchase_uuid'], 'stock_id' => $stock, 'accepted' => false, 'code' => 'COMPRA_CANCELADA', 'lines' => []];
        }
        if ($existing) {
            abort_unless((string) $existing->stock_id === $stock && (int) $existing->device_id === $deviceId
                && hash_equals($existing->huella, $hash), 409);

            return json_decode($existing->respuesta, true, 512, JSON_THROW_ON_ERROR);
        }
        $reject = function (string $code) use ($deviceId, $stock, $payload, $json, $hash): array {
            return $this->save($deviceId, $stock, $payload, $json, $hash, false, $code, []);
        };
        if (! $this->authorized($payload['usuario_pos_id'])) {
            return $reject('SIN_ACCESO');
        }
        if (DB::table('pos_compras_centrales')->where('compra_aceptada_uuid', $payload['purchase_uuid'])->lockForUpdate()->exists()) {
            return $reject('COMPRA_RECIBIDA');
        }
        if (DB::table('pos_compras_centrales')->where('stock_id', $stock)->where('supplier_id', $payload['supplier_id'])->where('documento_aceptado', $payload['invoice_cai'])->lockForUpdate()->exists()) {
            return $reject('COMPRA_RECIBIDA');
        }
        if (! DB::table('proveedores')->where('id', $payload['supplier_id'])->lockForUpdate()->exists()) {
            return $reject('PROVEEDOR_AUSENTE');
        }
        abort_unless(app(ChangeRecorder::class)->enabled(), 503);
        DB::table('pos_compra_lote_mutex')->where('id', 1)->lockForUpdate()->firstOrFail();
        $prepared = [];
        foreach ($payload['lines'] as $line) {
            $product = DB::table('productos')->where('id', $line['product_id'])->lockForUpdate()->first();
            $unit = $product ? DB::table('prod_unidades')->where('id', $product->unidad)->lockForUpdate()->first() : null;
            if (! $unit || bccomp((string) $unit->cantidad_c, '0', 4) <= 0) {
                return $reject('UNIDAD_AUSENTE');
            }
            if (bccomp((string) $unit->cantidad_c, $line['purchase_factor'], 4) !== 0) {
                return $reject('UNIDAD_CAMBIO');
            }
            $base = bcmul($line['quantity'], $line['purchase_factor'], 4);
            $cost = bcdiv(bcadd(bcdiv($line['cost'], $line['purchase_factor'], 5), '0.00005', 5), '1', 4);
            if (bccomp($line['quantity'], '0', 4) <= 0 || bccomp($base, '0', 4) <= 0
                || bccomp($base, '1000000000000000', 4) >= 0 || bccomp($cost, '1000000000000000', 4) >= 0
                || bccomp($base, $line['base_quantity'], 4) !== 0 || bccomp($cost, $line['base_cost'], 4) !== 0
                || $line['manufactured'] > $line['expires']) {
                return $reject('DETALLE_INVALIDO');
            }
            $lots = Lote::query()->where('producto', $line['product_id'])->where('lote', $line['lot'])
                ->whereRaw(DB::getDriverName() === 'mysql' ? 'BINARY lote = ?' : 'lote COLLATE BINARY = ?', [$line['lot']])
                ->whereDate('fecha_elab', $line['manufactured'])->whereDate('fecha_exp', $line['expires'])->lockForUpdate()->limit(2)->get();
            if ($lots->count() > 1) {
                return $reject('LOTE_AMBIGUO');
            }
            $prepared[] = [$line, $lots->first()];
        }
        $mapping = [];
        $created = [];
        $quantities = [];
        foreach ($prepared as [$line]) {
            $key = $line['product_id'].'|'.$line['lot'].'|'.$line['manufactured'].'|'.$line['expires'];
            $quantities[$key] = bcadd($quantities[$key] ?? '0', $line['base_quantity'], 4);
            if (bccomp($quantities[$key], '1000000000000000', 4) >= 0) {
                return $reject('DETALLE_INVALIDO');
            }
        }
        foreach ($prepared as [$line, $lot]) {
            $key = $line['product_id'].'|'.$line['lot'].'|'.$line['manufactured'].'|'.$line['expires'];
            $reused = $lot !== null;
            $lot ??= $created[$key] ?? Lote::query()->create([
                'compra' => null, 'producto' => $line['product_id'], 'lote' => $line['lot'],
                'fecha_elab' => $line['manufactured'], 'fecha_exp' => $line['expires'],
                'cantidad' => $quantities[$key], 'costo' => $line['base_cost'], 'isv' => $line['has_tax'],
            ]);
            $created[$key] = $lot;
            // Republish reused master lots too; receipt completion waits for their HQ copy.
            if ($reused) {
                app(ChangeRecorder::class)->record($lot, 'UPDATE');
            }
            // Existing lots retain their master data; this receipt preserves its own quantities/costs.
            $mapping[] = ['line_id' => $line['line_id'], 'lote_id' => (string) $lot->id, 'product_id' => $line['product_id'],
                'base_quantity' => $line['base_quantity'], 'purchase_factor' => $line['purchase_factor'], 'base_cost' => $line['base_cost']];
        }

        return $this->save($deviceId, $stock, $payload, $json, $hash, true, 'ACEPTADA', $mapping);
    }

    private function cancel(int $deviceId, string $stock, array $payload): array
    {
        Validator::make($payload, [
            'version' => ['required', 'integer', 'in:1'], 'request_id' => ['required', 'uuid'], 'receipt_request_id' => ['required', 'uuid'],
            'purchase_uuid' => ['required', 'uuid'], 'deletion_request_id' => ['required', 'uuid'],
            'stock_id' => ['required', 'string', 'regex:/\A[1-9][0-9]{0,19}\z/'], 'usuario_pos_id' => ['required', 'string', 'regex:/\A[1-9][0-9]{0,19}\z/'],
            'equipment' => ['required', 'string', 'max:128'],
        ])->validate();
        abort_unless($payload['stock_id'] === $stock, 403);
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $json);
        $previous = DB::table('pos_compra_cancelaciones')->where('receipt_request_id', $payload['receipt_request_id'])->lockForUpdate()->first();
        if ($previous) {
            abort_unless((int) $previous->device_id === $deviceId && (string) $previous->stock_id === $stock && $previous->request_id === $payload['request_id'] && hash_equals($previous->huella, $hash), 409);

            return json_decode($previous->respuesta, true, 512, JSON_THROW_ON_ERROR);
        }
        abort_unless($this->authorized($payload['usuario_pos_id'], 'compra.eliminar'), 403);
        $original = DB::table('pos_compras_centrales')->where('request_id', $payload['receipt_request_id'])->lockForUpdate()->first();
        abort_unless(! $original || ((int) $original->device_id === $deviceId && (string) $original->stock_id === $stock && $original->purchase_uuid === $payload['purchase_uuid']), 409);
        $response = ['version' => 1, 'request_id' => $payload['request_id'], 'receipt_request_id' => $payload['receipt_request_id'], 'purchase_uuid' => $payload['purchase_uuid'], 'stock_id' => $stock, 'accepted' => true, 'code' => 'CANCELADA'];
        DB::table('pos_compra_cancelaciones')->insert(['receipt_request_id' => $payload['receipt_request_id'], 'request_id' => $payload['request_id'], 'purchase_uuid' => $payload['purchase_uuid'],
            'deletion_request_id' => $payload['deletion_request_id'], 'device_id' => $deviceId, 'stock_id' => $stock, 'usuario_pos_id' => $payload['usuario_pos_id'], 'huella' => $hash, 'datos' => $json,
            'respuesta' => json_encode($response, JSON_THROW_ON_ERROR), 'created_at' => now()]);
        if ($original) {
            DB::table('pos_compras_centrales')->where('request_id', $payload['receipt_request_id'])->update(['compra_aceptada_uuid' => null, 'documento_aceptado' => null, 'cancelado_at' => now()]);
        }

        return $response;
    }

    private function authorized(string $user, string $action = 'compra.recibir'): bool
    {
        if (! DB::table('usuariospos as u')->join('rolespos as r', 'r.id', '=', 'u.rol_id')
            ->where('u.id', $user)->where('u.activo', true)->where('r.activo', true)->lockForUpdate()->exists()) {
            return false;
        }

        return DB::table('permisosusuariospos as p')->join('modulospos as m', 'm.id', '=', 'p.modulo')
            ->join('vistaspos as v', fn ($join) => $join->on('v.id', '=', 'p.vista')->on('v.modulo_id', '=', 'm.id'))
            ->join('botonespos as b', fn ($join) => $join->on('b.id', '=', 'p.actionvista')->on('b.vista_id', '=', 'v.id'))
            ->where('p.usuario', $user)->where('m.codigo', 'compras')->where('v.codigo', 'compras.documentos')
            ->where('b.codigo', $action)->where('m.activo', true)->where('v.activo', true)->where('b.activo', true)
            ->where(fn ($query) => $query->whereNull('p.lifetime')->orWhere('p.lifetime', '>', now()->timestamp))
            ->lockForUpdate()->exists();
    }

    private function save(int $device, string $stock, array $payload, string $json, string $hash, bool $accepted, string $code, array $mapping): array
    {
        $response = ['version' => 1, 'request_id' => $payload['request_id'], 'purchase_uuid' => $payload['purchase_uuid'],
            'stock_id' => $stock, 'accepted' => $accepted, 'code' => $code, 'lines' => $mapping];
        DB::table('pos_compras_centrales')->insert([
            'request_id' => $payload['request_id'], 'purchase_uuid' => $payload['purchase_uuid'],
            'compra_aceptada_uuid' => $accepted ? $payload['purchase_uuid'] : null,
            'device_id' => $device, 'stock_id' => $stock, 'usuario_pos_id' => $payload['usuario_pos_id'],
            'supplier_id' => $payload['supplier_id'], 'documento_aceptado' => $accepted ? $payload['invoice_cai'] : null,
            'huella' => $hash, 'datos' => $json, 'respuesta' => json_encode($response, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);

        return $response;
    }
}
