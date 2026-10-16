<?php

namespace App\Http\Controllers\API\Headquarters;

use App\Http\Controllers\Controller;
use App\Services\Headquarters\PosProductLotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PosProductLotController extends Controller
{
    public function handle(Request $request, PosProductLotService $service, string $action): JsonResponse
    {
        try {
            $data = $request->validate(['device_id' => ['required', 'integer', 'min:1'], 'client_uuid' => ['required', 'uuid'], 'payload' => ['present', 'array']]);

            return $this->sendResponse($service->execute($data['device_id'], $data['client_uuid'], $action, $data['payload']), 'ok');
        } catch (ValidationException $error) {
            return $this->sendError('El lote hijo no cumple el contrato POS.', $error->errors(), 422);
        } catch (HttpExceptionInterface $error) {
            return $this->sendError('Lote hijo no disponible para este dispositivo, stock o solicitud.', null, $error->getStatusCode());
        }
    }
}
