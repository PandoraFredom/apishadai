<?php

namespace App\Http\Controllers\API\Headquarters;

use App\DTO\HeadquartersRegistrationDTO;
use App\Exceptions\HeadquartersException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Headquarters\ConfirmRegistrationRequest;
use App\Http\Requests\Headquarters\RegistrationRequest;
use App\Interfaces\Headquarters\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $service) {}

    public function requestRegistration(RegistrationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = $this->service->requestRegistration(new HeadquartersRegistrationDTO(
                $data['name'], $request->ip(), Str::isUuid((string) $data['device_id'])
                    ? (string) $data['device_id'] : (int) $data['device_id'],
            ));

            return $this->sendResponse($result, 'Código en cola para enviar al administrador. El equipo aún no está autorizado.', 202)
                ->header('Cache-Control', 'no-store');
        } catch (HeadquartersException $exception) {
            return $this->sendError($exception->getMessage(), null, $exception->getCode());
        } catch (Throwable $exception) {
            Log::error('Error al solicitar el registro de Headquarters.', ['exception' => $exception::class]);

            return $this->sendError('No se pudo solicitar el registro.', null, 500);
        }
    }

    public function confirmRegistration(ConfirmRegistrationRequest $request): JsonResponse
    {
        try {
            $result = $this->service->confirmRegistration($request->validated(), $request->ip());

            return $this->sendResponse($result, 'Headquarters autorizado. Guarde la key; se entrega una sola vez.', 201)
                ->header('Cache-Control', 'no-store');
        } catch (HeadquartersException $exception) {
            return $this->sendError($exception->getMessage(), null, $exception->getCode());
        } catch (Throwable $exception) {
            Log::error('Error al confirmar el registro de Headquarters.', ['exception' => $exception::class]);

            return $this->sendError('No se pudo completar el registro.', null, 500);
        }
    }
}
