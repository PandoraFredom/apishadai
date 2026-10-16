<?php

namespace App\Services\Headquarters;

use App\Models\Device;
use App\Models\WorkLunch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PosAttendanceService
{
    public function __construct(private readonly PosAttendanceAlerts $alerts) {}

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
            if ($action === 'listar') {
                Validator::make($payload, ['consultas' => ['required', 'array', 'max:200'],
                    'consultas.*.usuario_pos_id' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
                    'consultas.*.fecha' => ['required', 'date_format:Y-m-d']])->validate();
                $records = [];
                foreach ($payload['consultas'] as $query) {
                    if ($this->activeUser($query['usuario_pos_id'])) {
                        $records[] = $this->state($query['usuario_pos_id'], $query['fecha']);
                    }
                }

                return ['version' => 1, 'stock_id' => (string) $device->stock, 'records' => $records];
            }
            abort_unless($action === 'registrar', 404);
            Validator::make($payload, [
                'version' => ['required', 'integer', 'in:1'], 'request_id' => ['required', 'uuid'],
                'usuario_pos_id' => ['required', 'regex:/\A[1-9][0-9]{0,19}\z/'],
                'equipo' => ['required', 'string', 'max:30', 'regex:/\A[A-Z0-9_.-]+\z/'],
                'accion' => ['required', 'in:ENTRADA,SALIDA,INICIAR_BREAK,FINALIZAR_BREAK'],
                'registrado_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
                'fecha' => ['required', 'date_format:Y-m-d'],
            ])->validate();
            $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $previous = DB::table('pos_asistencia_solicitudes')->where('request_id', $payload['request_id'])->lockForUpdate()->first();
            if ($previous) {
                abort_unless($previous->device_id === $deviceId && hash_equals($previous->huella, $hash), 409);

                $this->alerts->enqueue($payload['request_id']);

                return json_decode($previous->respuesta, true, flags: JSON_THROW_ON_ERROR);
            }
            // Lock the global POS identity, even before a day exists, across all stocks.
            $user = $this->activeUser($payload['usuario_pos_id']);
            $time = CarbonImmutable::createFromFormat('Y-m-d\TH:i:sP', $payload['registrado_at'])->setTimezone('America/Tegucigalpa');
            $date = $payload['fecha'];
            $session = WorkLunch::query()->where('usuario_pos', $payload['usuario_pos_id'])->where('work_date', $date)->lockForUpdate()->first();
            $button = in_array($payload['accion'], ['ENTRADA', 'SALIDA'], true) ? 'asistencia.jornada' : 'asistencia.break';
            $message = null;
            if (! $user || ! $this->allowed($payload['usuario_pos_id'], $button)) {
                $message = 'No tienes permiso para este registro de asistencia.';
            } elseif ($time->toDateString() !== $date || $time->greaterThan(CarbonImmutable::now('America/Tegucigalpa')->addMinutes(5))) {
                $message = 'La fecha del registro de asistencia no es válida.';
            } else {
                $message = $this->transitionError($session, $payload['accion'], $time);
            }
            if ($message === null) {
                $field = match ($payload['accion']) {
                    'ENTRADA' => 'wkstart_time', 'SALIDA' => 'wkend_time',
                    'INICIAR_BREAK' => 'lunch_start_time', 'FINALIZAR_BREAK' => 'lunch_end_time',
                };
                if (! $session) {
                    $session = WorkLunch::query()->create(['usuario' => null, 'usuario_pos' => $payload['usuario_pos_id'],
                        'device' => $deviceId, 'work_date' => $date, $field => $time->format('Y-m-d H:i:s')]);
                } else {
                    $session->update([$field => $time->format('Y-m-d H:i:s')]);
                }
            }
            $response = ['version' => 1, 'request_id' => $payload['request_id'], 'stock_id' => (string) $device->stock,
                'aceptada' => $message === null, 'mensaje' => $message ?? 'Registro de asistencia confirmado.',
                'jornada' => $this->state($payload['usuario_pos_id'], $date)];
            DB::table('pos_asistencia_solicitudes')->insert(['request_id' => $payload['request_id'],
                'device_id' => $deviceId, 'usuario_pos_id' => $payload['usuario_pos_id'], 'huella' => $hash,
                'datos' => json_encode($payload, JSON_THROW_ON_ERROR), 'respuesta' => json_encode($response, JSON_THROW_ON_ERROR),
                'created_at' => now()]);
            $this->alerts->enqueue($payload['request_id']);

            return $response;
        }, 3);
    }

    private function activeUser(string $id): ?object
    {
        return DB::table('usuariospos as u')->join('rolespos as r', 'r.id', '=', 'u.rol_id')
            ->where('u.id', $id)->where('u.activo', true)->where('r.activo', true)->select('u.id')->lockForUpdate()->first();
    }

    private function allowed(string $id, string $button): bool
    {
        return DB::table('permisosusuariospos as p')->join('modulospos as m', 'm.id', '=', 'p.modulo')
            ->join('vistaspos as v', fn ($join) => $join->on('v.id', '=', 'p.vista')->on('v.modulo_id', '=', 'm.id'))
            ->join('botonespos as b', fn ($join) => $join->on('b.id', '=', 'p.actionvista')->on('b.vista_id', '=', 'v.id'))
            ->where('p.usuario', $id)->where('m.codigo', 'asistencia')->where('v.codigo', 'asistencia.registro')
            ->where('b.codigo', $button)->where('m.activo', true)->where('v.activo', true)->where('b.activo', true)
            ->where(fn ($query) => $query->whereNull('p.lifetime')->orWhere('p.lifetime', '>', now()->timestamp))
            ->lockForUpdate()->exists();
    }

    private function transitionError(?WorkLunch $session, string $action, CarbonImmutable $time): ?string
    {
        $valid = match ($action) {
            'ENTRADA' => $session === null,
            'SALIDA' => $session && ! $session->wkend_time && (! $session->lunch_start_time || $session->lunch_end_time),
            'INICIAR_BREAK' => $session && ! $session->wkend_time && ! $session->lunch_start_time,
            'FINALIZAR_BREAK' => $session && ! $session->wkend_time && $session->lunch_start_time && ! $session->lunch_end_time,
        };
        if (! $valid) {
            return 'El estado de la jornada cambió. Consulta la asistencia antes de registrar.';
        }
        $last = $session?->lunch_end_time ?? $session?->lunch_start_time ?? $session?->wkstart_time;
        if ($last && $time->lessThan(CarbonImmutable::parse($last, 'America/Tegucigalpa'))) {
            return 'El registro debe ser posterior al último movimiento de la jornada.';
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function state(string $user, string $date): array
    {
        $session = WorkLunch::query()->where('usuario_pos', $user)->where('work_date', $date)->first();
        $time = fn (?string $value): ?string => $value ? CarbonImmutable::parse($value, 'America/Tegucigalpa')->format('Y-m-d\TH:i:sP') : null;

        return ['usuario_pos_id' => $user, 'fecha' => $date, 'entrada' => $time($session?->wkstart_time),
            'salida' => $time($session?->wkend_time), 'inicio_break' => $time($session?->lunch_start_time),
            'fin_break' => $time($session?->lunch_end_time)];
    }
}
