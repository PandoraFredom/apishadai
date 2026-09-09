<?php

namespace App\Http\Controllers;

use App\DTO\HorasLabDTO;
use App\Http\Requests\HorasLab\HorasLabRequest;
use App\Http\Requests\HorasLab\HorasLabUpdateRequest;
use App\Http\Resources\HorasLabResource;
use App\Interfaces\Config\HorasLabService;
use App\Models\HorasLab;
use Illuminate\Http\JsonResponse;

class HorasLabController extends Controller
{
    public function __construct(
        private readonly HorasLabService $horasLabService,
    ) {}

    public function index(): JsonResponse
    {
        $schedules = $this->horasLabService->paginate();

        return $this->sendResponse(
            HorasLabResource::collection($schedules),
            'Configuraciones de horas consultadas.',
            200,
            true,
        );
    }

    public function show(int $id): JsonResponse
    {
        $schedule = $this->horasLabService->findById($id);

        if (! $schedule) {
            return $this->sendError('Configuración de horas no encontrada.', null, 404);
        }

        return $this->sendResponse(
            HorasLabResource::make($schedule),
            'Configuración de horas consultada.',
        );
    }

    public function showByUser(int $userId): JsonResponse
    {
        $schedule = $this->horasLabService->findByUser($userId);

        if (! $schedule) {
            return $this->sendError(
                'empty',
                HorasLabResource::make(new HorasLab([
                    'usuario' => $userId,
                    'hora_inicio' => null,
                    'hora_fin' => null,
                ])),
                200,
            );
        }

        return $this->sendResponse(
            HorasLabResource::make($schedule),
            'Configuración de horas consultada.',
        );
    }

    public function store(HorasLabRequest $request): JsonResponse
    {
        try {
            $dto = HorasLabDTO::fromCreateRequest($request->validated());

            // Si ya existe una configuración para este usuario, la actualizamos
            $existing = $this->horasLabService->findByUser($dto->usuario);

            if ($existing) {
                $schedule = $this->horasLabService->updateSchedule(
                    $existing->id,
                    $dto->toUpdateArray(),
                );

                return $this->sendResponse(
                    true,
                    'Configuración de horas actualizada correctamente.',
                    201
                );
            }

            // Si no existe, creamos una nueva configuración
            $schedule = $this->horasLabService->createSchedule($dto->toCreateArray());

            if (! $schedule) {
                return $this->sendError('No se pudo crear la configuración de horas.', null, 500);
            }

            return $this->sendResponse(
                true,
                'Configuración de horas creada correctamente.',
                201
            );
        } catch (\DomainException $e) {
            return $this->sendError($e->getMessage(), null, $e->getCode() ?: 409);
        } catch (\Throwable $e) {
            $this->logError(__METHOD__, $e);

            return $this->sendError('No se pudo crear la configuración de horas.');
        }
    }

    public function update(HorasLabUpdateRequest $request): JsonResponse
    {
        try {
            $dto = HorasLabDTO::fromUpdateRequest($request->validated());
            $schedule = $this->horasLabService->updateSchedule(
                $dto->id,
                $dto->toUpdateArray(),
            );

            if (! $schedule) {
                return $this->sendError('Configuración de horas no encontrada.', null, 404);
            }

            return $this->sendResponse(
                HorasLabResource::make($schedule),
                'Configuración de horas actualizada correctamente.',
            );
        } catch (\Throwable $e) {
            $this->logError(__METHOD__, $e);

            return $this->sendError('No se pudo actualizar la configuración de horas.');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (! $this->horasLabService->delete($id)) {
            return $this->sendError(
                'Configuración de horas no encontrada o no se pudo eliminar.',
                null,
                404,
            );
        }

        return $this->sendResponse(
            true,
            'Configuración de horas eliminada correctamente.',
        );
    }
}
