<?php

namespace App\Services\Headquarters;

use App\Jobs\SendWorkLunchAlertJob;
use App\Models\WorkLunch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use LogicException;

class PosAttendanceAlerts
{
    public function enqueue(string $requestId): bool
    {
        return DB::transaction(function () use ($requestId): bool {
            $request = DB::table('pos_asistencia_solicitudes')->where('request_id', $requestId)->lockForUpdate()->first();
            if (! $request || $request->correo_encolado_at !== null) {
                return false;
            }
            $response = json_decode($request->respuesta, true, flags: JSON_THROW_ON_ERROR);
            if ($response['aceptada'] !== true) {
                return false;
            }
            if (config('queue.connections.database.driver') !== 'database'
                || DB::connection(config('queue.connections.database.connection'))->getName() !== DB::connection()->getName()) {
                throw new LogicException('La cola de asistencia debe compartir la conexión transaccional central.');
            }
            $payload = json_decode($request->datos, true, flags: JSON_THROW_ON_ERROR);
            $session = WorkLunch::query()->where('usuario_pos', $request->usuario_pos_id)
                ->where('work_date', $payload['fecha'])->firstOrFail();
            $event = match ($payload['accion']) {
                'ENTRADA' => 'work_started', 'SALIDA' => 'work_ended',
                'INICIAR_BREAK' => 'lunch_started', 'FINALIZAR_BREAK' => 'lunch_ended',
            };
            $state = $response['jornada'];
            $time = fn (?string $value): ?string => $value === null ? null
                : CarbonImmutable::parse($value)->setTimezone('America/Tegucigalpa')->format('Y-m-d H:i:s');
            $job = (new SendWorkLunchAlertJob($session->id, $event, [
                'wkstart_time' => $time($state['entrada']), 'wkend_time' => $time($state['salida']),
                'lunch_start_time' => $time($state['inicio_break']), 'lunch_end_time' => $time($state['fin_break']),
            ]))->onConnection('database')->beforeCommit();
            $job->attendanceRequestId = $requestId;
            // The database job and journal marker commit together; the worker cannot see either before commit.
            Bus::dispatch($job);
            DB::table('pos_asistencia_solicitudes')->where('request_id', $requestId)->update(['correo_encolado_at' => now()]);

            return true;
        }, 3);
    }
}
