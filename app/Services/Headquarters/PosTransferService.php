<?php

namespace App\Services\Headquarters;

use App\Models\Device;
use App\Models\Farma\Transferencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PosTransferService
{
    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function execute(int $deviceId, string $clientUuid, string $action, array $payload): array
    {
        return DB::transaction(function () use ($deviceId, $clientUuid, $action, $payload): array {
            $device = Device::with(['Estado', 'Stock.Estado'])->lockForUpdate()->find($deviceId);
            abort_unless($device && $device->hq_client_uuid === $clientUuid
                && strtoupper((string) $device->Estado?->descripcion) === 'ACTIVO'
                && strtoupper((string) $device->Stock?->Estado?->descripcion) === 'ACTIVO', 401);
            $stockId = (string) $device->stock;
            DB::table('stocks')->where('id', $stockId)->lockForUpdate()->first();

            return match ($action) {
                'enviar' => $this->send($stockId, $payload),
                'recibir' => $this->receive($stockId, $payload),
                'anular' => $this->cancel($stockId, $payload),
                'confirmar-anulacion' => $this->confirmCancellation($stockId, $payload),
                'listar' => $this->listing($stockId, $payload),
                default => abort(404),
            };
        }, 3);
    }

    /** @param array<string, mixed> $payload */
    private function authorize(array $payload, string $action): void
    {
        $id = $payload['usuario_pos_id'] ?? null;
        abort_unless(DB::table('usuariospos as u')->join('rolespos as r', 'r.id', '=', 'u.rol_id')
            ->where('u.id', $id)->where('u.activo', true)->where('r.activo', true)->lockForUpdate()->exists(), 403);
        abort_unless(DB::table('permisosusuariospos as p')
            ->join('modulospos as m', 'm.id', '=', 'p.modulo')
            ->join('vistaspos as v', function ($join): void {
                $join->on('v.id', '=', 'p.vista')->on('v.modulo_id', '=', 'm.id');
            })
            ->join('botonespos as b', function ($join): void {
                $join->on('b.id', '=', 'p.actionvista')->on('b.vista_id', '=', 'v.id');
            })
            ->where('p.usuario', $id)->where('m.codigo', 'transferencias')->where('v.codigo', in_array($action, ['enviar', 'anular'], true) ? 'transferencias.enviadas' : 'transferencias.recibidas')
            ->where('b.codigo', 'transferencia.'.(in_array($action, ['enviar', 'anular'], true) ? 'enviadas' : 'recibidas').'.'.$action)->where('m.activo', true)->where('v.activo', true)->where('b.activo', true)
            ->where(fn ($query) => $query->whereNull('p.lifetime')->orWhere('p.lifetime', '>', now()->timestamp))
            ->lockForUpdate()->exists(), 403);
    }

    /** @return array<string, mixed> */
    private function commonRules(): array
    {
        return ['version' => ['required', 'integer', 'in:1'], 'request_id' => ['required', 'uuid'],
            'transferencia_uuid' => ['required', 'uuid'], 'usuario_pos_id' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
            'equipo' => ['required', 'string', 'max:30', 'regex:/\A[A-Z0-9_.-]+\z/'],
            'terminal_nombre' => ['required', 'string', 'max:220']];
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function send(string $stockId, array $payload): array
    {
        Validator::make($payload, $this->commonRules() + [
            'unidad' => ['required', 'in:BASE'], 'stock_de' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
            'stock_para' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'], 'referencia' => ['required', 'string', 'max:100'],
            'tipo_id' => ['sometimes', 'string', 'regex:/\A[1-9][0-9]{0,19}\z/'],
            'tipo_nombre' => ['required_with:tipo_id', 'string', 'max:200'],
            'observacion' => ['nullable', 'string', 'max:1000'], 'lineas' => ['required', 'array', 'min:1', 'max:1000'],
            'lineas.*.lote_id' => ['required', 'distinct', 'regex:/\A[1-9][0-9]{0,19}\z/'],
            'lineas.*.producto_id' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
            'lineas.*.numero_lote' => ['nullable', 'string', 'max:200'], 'lineas.*.vencimiento' => ['nullable', 'date_format:Y-m-d'],
            'lineas.*.cantidad' => ['required', 'string', 'regex:/\A[0-9]{1,15}(\.[0-9]{1,4})?\z/'],
            'lineas.*.costo_unitario' => ['nullable', 'string', 'regex:/\A[0-9]{1,15}(\.[0-9]{1,4})?\z/'],
        ])->validate();
        $this->authorize($payload, 'enviar');
        abort_unless((string) $payload['stock_de'] === $stockId && (string) $payload['stock_para'] !== $stockId, 403);
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $json);
        $existing = DB::table('pos_transferencias_centrales')->where('transferencia_uuid', $payload['transferencia_uuid'])
            ->orWhere('solicitud_envio', $payload['request_id'])->lockForUpdate()->first();
        if ($existing) {
            abort_unless($existing->solicitud_envio === $payload['request_id'] && $existing->transferencia_uuid === $payload['transferencia_uuid']
                && $existing->stock_de == $stockId && hash_equals($existing->huella_envio, $hash), 409);

            return $this->ack($existing, $payload['request_id']);
        }
        if (isset($payload['tipo_id'])) {
            abort_unless(DB::table('transferencias_tipo')->where('id', $payload['tipo_id'])->lockForUpdate()->exists(), 422);
        }
        abort_unless(DB::table('stocks as s')->join('stock_estado as e', 'e.id', '=', 's.estado')
            ->where('s.id', $payload['stock_para'])->where('e.descripcion', 'ACTIVO')->lockForUpdate()->exists(), 422);
        foreach ($payload['lineas'] as $line) {
            abort_unless(trim(str_replace('.', '', $line['cantidad']), '0') !== '', 422);
            abort_unless(DB::table('lotes')->where('id', $line['lote_id'])->where('producto', $line['producto_id'])->lockForUpdate()->exists(), 422);
        }
        $id = DB::table('pos_transferencias_centrales')->insertGetId([
            'transferencia_uuid' => $payload['transferencia_uuid'], 'solicitud_envio' => $payload['request_id'],
            'huella_envio' => $hash, 'stock_de' => $stockId, 'stock_para' => $payload['stock_para'],
            'usuario_envia_pos' => $payload['usuario_pos_id'], 'datos' => $json, 'estado' => 'PENDIENTE', 'enviado_at' => now(),
        ]);

        return $this->ack(DB::table('pos_transferencias_centrales')->find($id), $payload['request_id']);
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function receive(string $stockId, array $payload): array
    {
        Validator::make($payload, $this->commonRules() + [
            'central_id' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'], 'stock_para' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
        ])->validate();
        $this->authorize($payload, 'recibir');
        $row = DB::table('pos_transferencias_centrales')->where('id', $payload['central_id'])->lockForUpdate()->first();
        abort_unless($row && (string) $row->stock_para === $stockId && (string) $payload['stock_para'] === $stockId
            && strtolower($row->transferencia_uuid) === strtolower($payload['transferencia_uuid']), 403);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($row->solicitud_recepcion !== null) {
            abort_unless($row->solicitud_recepcion === $payload['request_id'] && hash_equals($row->huella_recepcion, $hash), 409);

            return $this->ack($row, $payload['request_id']);
        }
        abort_unless(in_array($row->estado, ['PENDIENTE', 'ANULANDO'], true), 409);
        if ($row->legacy_id !== null) {
            $legacy = Transferencia::with('estadoRecepcionDetalle')->lockForUpdate()->findOrFail($row->legacy_id);
            abort_unless((string) $legacy->stock_para === $stockId && strtoupper((string) $legacy->estadoRecepcionDetalle?->descripcion) === 'PENDIENTE', 409);
            $status = DB::table('transferencia_estado')->where('descripcion', 'RECIBIDA')->value('id');
            abort_if($status === null, 422);
            $legacy->update(['estado_recepcion' => $status, 'recibido_at' => now()]);
        }
        DB::table('pos_transferencias_centrales')->where('id', $row->id)->update([
            'solicitud_recepcion' => $payload['request_id'], 'huella_recepcion' => $hash,
            'usuario_recibe_pos' => $payload['usuario_pos_id'], 'estado' => 'RECIBIDA', 'recibido_at' => now(),
        ]);

        return $this->ack(DB::table('pos_transferencias_centrales')->find($row->id), $payload['request_id']);
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function cancel(string $stockId, array $payload): array
    {
        Validator::make($payload, $this->commonRules() + [
            'stock_de' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
            'motivo' => ['required', 'string', 'max:500'],
        ])->validate();
        $this->authorize($payload, 'anular');
        $row = DB::table('pos_transferencias_centrales')->where('transferencia_uuid', $payload['transferencia_uuid'])->lockForUpdate()->first();
        abort_unless($row && (string) $row->stock_de === $stockId && (string) $payload['stock_de'] === $stockId && $row->legacy_id === null, 403);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($row->solicitud_anulacion !== null) {
            abort_unless(strtolower($row->solicitud_anulacion) === strtolower($payload['request_id']) && hash_equals($row->huella_anulacion, $hash), 409);

            return $this->ack($row, $payload['request_id']);
        }
        abort_unless(in_array($row->estado, ['PENDIENTE', 'RECIBIDA'], true), 409);
        DB::table('pos_transferencias_centrales')->where('id', $row->id)->update([
            'solicitud_anulacion' => $payload['request_id'], 'huella_anulacion' => $hash,
            'usuario_anula_pos' => $payload['usuario_pos_id'], 'motivo_anulacion' => $payload['motivo'],
            'anulacion_solicitada_at' => now(), 'estado' => $row->estado === 'RECIBIDA' ? 'RECIBIDA' : 'ANULANDO',
        ]);

        return $this->ack(DB::table('pos_transferencias_centrales')->find($row->id), $payload['request_id']);
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function confirmCancellation(string $stockId, array $payload): array
    {
        Validator::make($payload, [
            'version' => ['required', 'integer', 'in:1'], 'request_id' => ['required', 'uuid'],
            'transferencia_uuid' => ['required', 'uuid'],
            'central_id' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
            'stock_para' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
        ])->validate();
        $row = DB::table('pos_transferencias_centrales')->where('id', $payload['central_id'])->lockForUpdate()->first();
        abort_unless($row && (string) $row->stock_para === $stockId && (string) $payload['stock_para'] === $stockId
            && strtolower($row->transferencia_uuid) === strtolower($payload['transferencia_uuid']), 403);
        abort_unless($row->solicitud_anulacion !== null && strtolower($row->solicitud_anulacion) === strtolower($payload['request_id']), 409);
        if ($row->estado === 'ANULANDO') {
            abort_unless($row->solicitud_recepcion === null && $row->recibido_at === null, 409);
            DB::table('pos_transferencias_centrales')->where('id', $row->id)->update(['estado' => 'ANULADA', 'anulada_at' => now()]);
        }
        abort_unless(in_array($row->estado, ['ANULANDO', 'ANULADA', 'RECIBIDA'], true), 409);

        return $this->ack(DB::table('pos_transferencias_centrales')->find($row->id), $payload['request_id']);
    }

    private function listing(string $stockId, array $payload): array
    {
        $data = Validator::make($payload, ['after_id' => ['sometimes', 'integer', 'min:0'], 'legacy_after_id' => ['sometimes', 'integer', 'min:0']])->validate();
        $legacy = Transferencia::with(['estadoDetalle', 'estadoRecepcionDetalle', 'detalles.loteDetalle'])
            ->where('stock_para', $stockId)->where('id', '>', $data['legacy_after_id'] ?? 0)
            ->whereHas('estadoDetalle', fn ($query) => $query->where('descripcion', 'ENVIADA'))
            ->whereHas('estadoRecepcionDetalle', fn ($query) => $query->where('descripcion', 'PENDIENTE'))
            ->orderBy('id')->limit(100)->lockForUpdate()->get();
        foreach ($legacy as $transfer) {
            if (DB::table('pos_transferencias_centrales')->where('legacy_id', $transfer->id)->exists()) {
                continue;
            }
            $uuid = (string) Str::uuid();
            $lines = $transfer->detalles->map(function ($detail): array {
                abort_unless($detail->loteDetalle && $detail->cantidad > 0, 422);

                return ['lote_id' => (string) $detail->lote, 'producto_id' => (string) $detail->loteDetalle->producto,
                    'numero_lote' => $detail->loteDetalle->lote, 'vencimiento' => $detail->loteDetalle->fecha_exp?->format('Y-m-d'),
                    'cantidad' => (string) $detail->cantidad];
            })->all();
            abort_if($lines === [], 422);
            $body = json_encode(['version' => 1, 'unidad' => 'BASE', 'request_id' => $uuid, 'transferencia_uuid' => $uuid,
                'stock_de' => (string) $transfer->stock_de, 'stock_para' => $stockId, 'usuario_pos_id' => '0',
                'equipo' => 'HQ', 'terminal_nombre' => 'HQ', 'referencia' => 'HQ-'.$transfer->id, 'lineas' => $lines], JSON_THROW_ON_ERROR);
            DB::table('pos_transferencias_centrales')->insert([
                'transferencia_uuid' => $uuid, 'solicitud_envio' => $uuid, 'huella_envio' => hash('sha256', $body),
                'stock_de' => $transfer->stock_de, 'stock_para' => $stockId, 'legacy_id' => $transfer->id,
                'datos' => $body, 'estado' => 'PENDIENTE', 'enviado_at' => $transfer->enviado_at ?? now(),
            ]);
        }
        $rows = DB::table('pos_transferencias_centrales as t')->join('stocks as o', 'o.id', '=', 't.stock_de')
            ->join('stocks as d', 'd.id', '=', 't.stock_para')->where('t.id', '>', $data['after_id'] ?? 0)
            ->where(fn ($query) => $query->where('t.stock_de', $stockId)->orWhere('t.stock_para', $stockId))
            ->orderBy('t.id')->limit(100)->get(['t.*', 'o.descripcion as origen_nombre', 'd.descripcion as destino_nombre']);
        $stocks = DB::table('stocks as s')->join('stock_estado as e', 'e.id', '=', 's.estado')->where('e.descripcion', 'ACTIVO')
            ->orderBy('s.id')->get(['s.id', 's.descripcion'])->map(fn ($stock): array => ['id' => (string) $stock->id, 'nombre' => $stock->descripcion])->all();

        return ['stock_id' => $stockId, 'stock_nombre' => DB::table('stocks')->where('id', $stockId)->value('descripcion'), 'stocks' => $stocks,
            'records' => $rows->map(fn ($row): array => ['id' => (string) $row->id, 'datos' => $row->datos, 'estado' => $row->estado,
                'hq_transferencia_id' => $row->legacy_id === null ? null : (string) $row->legacy_id,
                'enviado_at' => Carbon::parse($row->enviado_at)->toIso8601String(),
                'solicitud_anulacion' => $row->solicitud_anulacion, 'motivo_anulacion' => $row->motivo_anulacion,
                'origen_nombre' => $row->origen_nombre, 'destino_nombre' => $row->destino_nombre])->all(),
            'next_id' => (string) ($rows->last()?->id ?? ($data['after_id'] ?? 0)), 'has_more' => $rows->count() === 100,
            'legacy_next_id' => (string) ($legacy->last()?->id ?? ($data['legacy_after_id'] ?? 0)), 'legacy_has_more' => $legacy->count() === 100];
    }

    /** @return array<string, mixed> */
    private function ack(object $row, string $requestId): array
    {
        return ['id' => (string) $row->id, 'request_id' => $requestId, 'transferencia_uuid' => $row->transferencia_uuid, 'estado' => $row->estado];
    }
}
