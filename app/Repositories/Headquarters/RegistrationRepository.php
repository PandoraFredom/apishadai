<?php

namespace App\Repositories\Headquarters;

use App\DTO\HeadquartersRegistrationDTO;
use App\Exceptions\HeadquartersException;
use App\Interfaces\Headquarters\RegistrationService;
use App\Jobs\SendHeadquartersApprovalJob;
use App\Mail\HeadquartersApprovalMail;
use App\Models\AppConfig;
use App\Models\Device;
use App\Models\Stocks;
use App\Models\SyncChange;
use App\Services\Headquarters\ChangeRecorder;
use App\Services\Headquarters\HeadquartersClient;
use App\Services\Headquarters\OutboxSender;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegistrationRepository implements RegistrationService
{
    public function __construct(private readonly HeadquartersClient $client) {}

    public function requestRegistration(HeadquartersRegistrationDTO $data): array
    {
        $recipient = trim((string) AppConfig::query()->value('mail_cc1'));
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new HeadquartersException('No está configurado el correo administrador de Headquarters.', 503);
        }
        [$device, $binding] = DB::transaction(function () use ($data): array {
            $query = is_int($data->deviceId)
                ? Device::whereKey($data->deviceId)
                : Device::where('hq_pairing_uuid', $data->deviceId);
            $device = $query->lockForUpdate()->first();
            if (! $device) {
                throw new HeadquartersException('El UUID ya no está emparejado con un dispositivo. Revisa la vista Dispositivos.', 422);
            }
            if (mb_strtoupper((string) $device->Estado?->descripcion) !== 'ACTIVO' || $device->stock < 1) {
                throw new HeadquartersException('El dispositivo debe estar activo y tener un stock.', 422);
            }
            app(ChangeRecorder::class)->record($device, 'UPDATE');
            $binding = SyncChange::where('entity', 'devices')->where('entity_id', $device->id)->orderByDesc('id')->firstOrFail();

            return [$device, $binding];
        }, 3);
        $this->client->send('POST', 'changes', ['changes' => [app(OutboxSender::class)->serialize($binding)]]);
        $result = $this->client->send('POST', 'registrations', ['name' => $data->name, 'ip' => $data->ip,
            'stock_id' => $device->stock, 'source_device_id' => $device->id, 'hq_client_uuid' => $device->hq_client_uuid]);
        try {
            app(Dispatcher::class)->dispatch(new SendHeadquartersApprovalJob($recipient, new HeadquartersApprovalMail(
                $result['registration_id'], $data->name, $data->ip,
                Stocks::findOrFail($device->stock)->descripcion,
                $result['approval_code'], (int) config('headquarters.registration_minutes'), $device->id, $device->displayname,
            ), CarbonImmutable::parse($result['expires_at'])->timestamp));
            Log::info('Autorización de Headquarters agregada a la cola.', [
                'registration_id' => $result['registration_id'], 'source_device_id' => $device->id,
            ]);
        } catch (Throwable $exception) {
            try {
                $this->client->send('DELETE', 'registrations/'.$result['registration_id']);
            } catch (Throwable $revokeException) {
                Log::warning('No se pudo revocar la solicitud; vencerá automáticamente.', ['exception' => $revokeException::class]);
            }
            Log::warning('No se pudo encolar la autorización de Headquarters.', ['exception' => $exception::class]);
            throw new HeadquartersException('No se pudo encolar el código de autorización. Solicite un nuevo registro.', 503);
        }
        unset($result['approval_code']);

        return $result;
    }

    public function confirmRegistration(array $data, string $ip): array
    {
        return $this->client->send('POST', 'registrations/confirm', [...$data, 'ip' => $ip]);
    }
}
