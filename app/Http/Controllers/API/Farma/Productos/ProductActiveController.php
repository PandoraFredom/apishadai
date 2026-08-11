<?php

namespace App\Http\Controllers\API\Farma\Productos;

use App\DTOs\Farma\ProductActiveData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farma\Productos\ProductActiveRequest;
use App\Http\Resources\Farma\ProductActiveResource;
use App\Interfaces\Farma\ProductActiveService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProductActiveController extends Controller
{
    public function __construct(private readonly ProductActiveService $repository) {}

    public function index(): JsonResponse
    {
        return $this->sendResponse(
            ProductActiveResource::collection($this->repository->paginate()),
            'ok',
            200,
            true,
        );
    }

    public function store(ProductActiveRequest $request): JsonResponse
    {
        try {
            $data = ProductActiveData::fromValidated($request->validated());

            return $this->sendResponse(
                ProductActiveResource::make($this->repository->create($data->toArray())),
                'Principio activo asociado correctamente.',
                201,
            );
        } catch (Throwable $throwable) {
            return $this->databaseError($throwable, 'No se pudo crear la asociación.');
        }
    }

    public function show(int $id): JsonResponse
    {
        $association = $this->repository->find($id);

        return $association === null
            ? $this->sendError('Asociación no encontrada.', null, 404)
            : $this->sendResponse(ProductActiveResource::make($association), 'ok');
    }

    public function update(ProductActiveRequest $request): JsonResponse
    {
        try {
            $data = ProductActiveData::fromValidated($request->validated());
            $association = $this->repository->update($request->integer('id'), $data->toArray());

            return $association === null
                ? $this->sendError('Asociación no encontrada.', null, 404)
                : $this->sendResponse(
                    ProductActiveResource::make($association),
                    'Asociación actualizada correctamente.',
                );
        } catch (Throwable $throwable) {
            return $this->databaseError($throwable, 'No se pudo actualizar la asociación.');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->repository->delete($id)
            ? $this->sendResponse(true, 'Asociación eliminada correctamente.')
            : $this->sendError('Asociación no encontrada.', null, 404);
    }

    private function databaseError(Throwable $throwable, string $fallback): JsonResponse
    {
        $this->logError('ProductActiveController', $throwable);

        if ($throwable instanceof QueryException && in_array((string) $throwable->getCode(), ['23000', '23503'], true)) {
            return $this->sendError(
                'La asociación ya existe o contiene referencias inválidas.',
                null,
                409,
            );
        }

        return $this->sendError($fallback, null, 500);
    }
}
