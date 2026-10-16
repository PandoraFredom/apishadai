<?php

namespace App\Services\Headquarters;

use App\Models\Device;
use App\Models\Farma\Lote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PosProductLotService
{
    public function execute(int $deviceId, string $clientUuid, string $action, array $payload): array
    {
        abort_unless(extension_loaded('bcmath'), 503);

        return DB::transaction(function () use ($deviceId, $clientUuid, $action, $payload): array {
            $device = Device::with(['Estado', 'Stock.Estado'])->lockForUpdate()->find($deviceId);
            abort_unless($device && $device->hq_client_uuid === $clientUuid && strtoupper((string) $device->Estado?->descripcion) === 'ACTIVO' && strtoupper((string) $device->Stock?->Estado?->descripcion) === 'ACTIVO', 401);
            $stock = (string) $device->stock;
            DB::table('stocks')->where('id', $stock)->lockForUpdate()->first();
            if ($action === 'consultar') {
                Validator::make($payload, ['request_id' => ['required', 'uuid']])->validate();
                $row = DB::table('pos_lotes_hijos_centrales')->where('request_id', $payload['request_id'])->where('stock_id', $stock)->first();
                abort_unless($row, 404);

                return json_decode($row->respuesta, true, 512, JSON_THROW_ON_ERROR);
            }
            abort_unless($action === 'preparar', 404);

            return $this->prepare($deviceId, $stock, $payload);
        }, 3);
    }

    private function prepare(int $device, string $stock, array $p): array
    {
        $id = ['required', 'string', 'regex:/\A[1-9][0-9]{0,19}\z/'];
        $decimal = ['required', 'string', 'regex:/\A[0-9]{1,15}(\.[0-9]{1,4})?\z/'];
        Validator::make($p, [
            'version' => ['required', 'integer', 'in:1'], 'request_id' => ['required', 'uuid'], 'stock_id' => $id, 'usuario_pos_id' => $id, 'product_id' => $id, 'parent_id' => $id,
            'equipment' => ['required', 'string', 'max:30'], 'purchase_parent' => $decimal, 'sale_parent' => $decimal, 'purchase_child' => $decimal, 'sale_child' => $decimal,
            'lines' => ['required', 'array', 'min:1', 'max:1000'], 'lines.*.parent_lot_id' => array_merge($id, ['distinct']),
            'lines.*.lot' => ['required', 'string', 'max:200'], 'lines.*.manufactured' => ['present', 'nullable', 'date_format:Y-m-d'], 'lines.*.expires' => ['present', 'nullable', 'date_format:Y-m-d'],
        ])->validate();
        abort_unless($p['stock_id'] === $stock, 403);
        $json = json_encode($p, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $json);
        $existing = DB::table('pos_lotes_hijos_centrales')->where('request_id', $p['request_id'])->lockForUpdate()->first();
        if ($existing) {
            abort_unless((string) $existing->stock_id === $stock && (int) $existing->device_id === $device && hash_equals($existing->huella, $hash), 409);

            return json_decode($existing->respuesta, true, 512, JSON_THROW_ON_ERROR);
        }
        $reject = fn (string $code) => $this->save($device, $stock, $p, $json, $hash, false, $code, []);
        if (! $this->authorized($p['usuario_pos_id'])) {
            return $reject('SIN_ACCESO');
        }
        DB::table('producto_relacion_mutex')->where('id', 1)->lockForUpdate()->firstOrFail();
        $child = DB::table('productos')->where('id', $p['product_id'])->lockForUpdate()->first();
        if (! $child || (string) $child->producto_padre_id !== $p['parent_id'] || $p['product_id'] === $p['parent_id']) {
            return $reject('RELACION_CAMBIO');
        }
        $parent = DB::table('productos')->where('id', $p['parent_id'])->lockForUpdate()->first();
        $cu = DB::table('prod_unidades')->where('id', $child->unidad)->lockForUpdate()->first();
        $pu = $parent ? DB::table('prod_unidades')->where('id', $parent->unidad)->lockForUpdate()->first() : null;
        foreach (['purchase_parent' => [$pu, 'cantidad_c'], 'sale_parent' => [$pu, 'cantidad_v'], 'purchase_child' => [$cu, 'cantidad_c'], 'sale_child' => [$cu, 'cantidad_v']] as $field => [$unit,$column]) {
            if (! $unit || bccomp((string) $unit->$column, '0', 4) <= 0 || bccomp((string) $unit->$column, $p[$field], 4) !== 0 || bccomp($p[$field], bcdiv($p[$field], '1', 0), 4) !== 0) {
                return $reject('UNIDAD_CAMBIO');
            }
        }
        if (bccomp(bcmod($p['purchase_parent'], $p['sale_parent'], 4), '0', 4) !== 0 || bccomp(bcmod($p['purchase_child'], $p['sale_child'], 4), '0', 4) !== 0) {
            return $reject('UNIDAD_CAMBIO');
        }
        abort_unless(app(ChangeRecorder::class)->enabled(), 503);
        // Share the mutex with purchases: both channels may create the same master lot.
        DB::table('pos_compra_lote_mutex')->where('id', 1)->lockForUpdate()->firstOrFail();
        $prepared = [];
        foreach ($p['lines'] as $line) {
            $father = Lote::query()->where('id', $line['parent_lot_id'])->lockForUpdate()->first();
            if (! $father || (string) $father->producto !== $p['parent_id']) {
                return $reject('LOTE_AUSENTE');
            }
            $manufactured = $father->fecha_elab?->format('Y-m-d');
            $expires = $father->fecha_exp?->format('Y-m-d');
            if ($father->lote !== $line['lot'] || $manufactured !== $line['manufactured'] || $expires !== $line['expires'] || $manufactured !== null && $expires !== null && $manufactured > $expires || $expires !== null && $expires < now('America/Tegucigalpa')->toDateString()) {
                return $reject('LOTE_CAMBIO');
            }
            $query = Lote::query()->where('producto', $p['product_id'])->where('lote', $line['lot'])
                ->whereRaw(DB::getDriverName() === 'mysql' ? 'BINARY lote = ?' : 'lote COLLATE BINARY = ?', [$line['lot']]);
            $manufactured === null ? $query->whereNull('fecha_elab') : $query->whereDate('fecha_elab', $manufactured);
            $expires === null ? $query->whereNull('fecha_exp') : $query->whereDate('fecha_exp', $expires);
            $lots = $query->lockForUpdate()->limit(2)->get();
            if ($lots->count() > 1) {
                return $reject('LOTE_AMBIGUO');
            }
            $cost = $father->costo === null ? null : bcdiv(bcadd(bcdiv(bcmul((string) $father->costo, $p['purchase_parent'], 8), $p['purchase_child'], 5), '0.00005', 5), '1', 4);
            if ($cost !== null && (bccomp($cost, '0', 4) < 0 || bccomp($cost, '1000000000000000', 4) >= 0)) {
                return $reject('UNIDAD_CAMBIO');
            }
            $prepared[] = [$line, $lots->first(), $cost];
        }
        $mapping = [];
        $created = [];
        foreach ($prepared as [$line,$lot,$cost]) {
            $key = json_encode([$line['lot'], $line['manufactured'], $line['expires']], JSON_THROW_ON_ERROR);
            $reused = $lot !== null;
            $lot ??= $created[$key] ?? Lote::query()->create(['compra' => null, 'producto' => $p['product_id'], 'lote' => $line['lot'], 'fecha_elab' => $line['manufactured'], 'fecha_exp' => $line['expires'], 'cantidad' => null, 'costo' => $cost, 'isv' => null]);
            $created[$key] = $lot;
            if ($reused) {
                app(ChangeRecorder::class)->record($lot, 'UPDATE');
            }
            $mapping[] = ['parent_lot_id' => $line['parent_lot_id'], 'lote_id' => (string) $lot->id, 'product_id' => $p['product_id'], 'lot' => $line['lot'], 'manufactured' => $line['manufactured'], 'expires' => $line['expires']];
        }

        return $this->save($device, $stock, $p, $json, $hash, true, 'ACEPTADA', $mapping);
    }

    private function authorized(string $user): bool
    {
        if (! DB::table('usuariospos as u')->join('rolespos as r', 'r.id', '=', 'u.rol_id')->where('u.id', $user)->where('u.activo', true)->where('r.activo', true)->lockForUpdate()->exists()) {
            return false;
        }

        return DB::table('permisosusuariospos as p')->join('modulospos as m', 'm.id', '=', 'p.modulo')
            ->join('vistaspos as v', fn ($j) => $j->on('v.id', '=', 'p.vista')->on('v.modulo_id', '=', 'm.id'))
            ->join('botonespos as b', fn ($j) => $j->on('b.id', '=', 'p.actionvista')->on('b.vista_id', '=', 'v.id'))
            ->where('p.usuario', $user)->where('m.codigo', 'pos')->where('v.codigo', 'pos.facturas')->where('b.codigo', 'factura.emitir')
            ->where('m.activo', true)->where('v.activo', true)->where('b.activo', true)->where(fn ($q) => $q->whereNull('p.lifetime')->orWhere('p.lifetime', '>', now()->timestamp))->lockForUpdate()->exists();
    }

    private function save(int $device, string $stock, array $p, string $json, string $hash, bool $accepted, string $code, array $mapping): array
    {
        $response = ['version' => 1, 'request_id' => $p['request_id'], 'stock_id' => $stock, 'accepted' => $accepted, 'code' => $code, 'lines' => $mapping];
        DB::table('pos_lotes_hijos_centrales')->insert(['request_id' => $p['request_id'], 'device_id' => $device, 'stock_id' => $stock, 'usuario_pos_id' => $p['usuario_pos_id'], 'producto_id' => $p['product_id'], 'huella' => $hash, 'datos' => $json, 'respuesta' => json_encode($response,JSON_THROW_ON_ERROR), 'created_at' => now()]);

        return $response;
    }
}
