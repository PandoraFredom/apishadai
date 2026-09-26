<?php

namespace App\Http\Controllers\API\Utils;

use App\Http\Controllers\Controller;
use App\Interfaces\Utils\BibliaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Throwable;
use UnexpectedValueException;

final class BibliaController extends Controller
{
    public function __construct(private readonly BibliaService $service) {}

    public function random(): JsonResponse
    {
        try {
            return $this->sendResponse($this->service->getRandom()->toArray(), 'ok');
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            $this->logError(__METHOD__, $exception);

            return $this->sendError('No se pudo obtener el versículo bíblico.', false, 502);
        } catch (Throwable $exception) {
            $this->logError(__METHOD__, $exception);

            return $this->sendError('El servicio bíblico no está disponible.', false, 500);
        }
    }
}
