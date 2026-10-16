<?php

namespace App\Http\Controllers\API\Headquarters;

use App\Http\Controllers\Controller;
use App\Services\Headquarters\PosTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PosTransferController extends Controller
{
    public function handle(Request $request, PosTransferService $service, string $action): JsonResponse
    {
        try {
            $data = $request->validate([
                'device_id' => ['required', 'integer', 'min:1'],
                'client_uuid' => ['required', 'uuid'],
                'payload' => ['present', 'array'],
            ]);
            $result = $service->execute($data['device_id'], $data['client_uuid'], $action, $data['payload']);

            return $this->sendResponse($result, 'ok');
        } catch (ValidationException $exception) {
            return $this->sendError('El documento de transferencia no cumple el contrato POS.', $exception->errors(), 422);
        } catch (HttpExceptionInterface $exception) {
            return $this->sendError('La transferencia no está disponible para este stock, usuario o solicitud.', null, $exception->getStatusCode());
        }
    }
}
