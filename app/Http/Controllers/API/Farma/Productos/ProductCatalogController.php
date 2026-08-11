<?php

namespace App\Http\Controllers\API\Farma\Productos;

use App\DTOs\Farma\ProductCatalogData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farma\Productos\ProductCatalogRequest;
use App\Http\Resources\Farma\ProductCatalogResource;
use App\Interfaces\Farma\ProductCatalogService;
use App\Interfaces\Farma\ProductCatalogRegistryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProductCatalogController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $repository,
        private readonly ProductCatalogRegistryService $registry,
    ) {}

    public function index(string $catalogo): JsonResponse
    {
        if (! $this->registry->has($catalogo)) {
            return $this->sendError('Catálogo no encontrado.', null, 404);
        }

        return $this->sendResponse(
            ProductCatalogResource::collection($this->repository->paginate($catalogo)),
            'ok',
            200,
            true,
        );
    }

    public function store(ProductCatalogRequest $request, string $catalogo): JsonResponse
    {
        try {
            $data = ProductCatalogData::fromValidated(
                $catalogo,
                $request->validated(),
                $this->registry,
            );
            $model = $this->repository->create($catalogo, $data->attributes);

            return $this->sendResponse(
                ProductCatalogResource::make($model),
                'Registro creado correctamente.',
                201,
            );
        } catch (Throwable $throwable) {
            $this->logError('ProductCatalogController store', $throwable);

            return $this->databaseError($throwable, 'No se pudo crear el registro.');
        }
    }

    public function show(string $catalogo, int $id): JsonResponse
    {
        $model = $this->repository->find($catalogo, $id);

        return $model === null
            ? $this->sendError('Registro no encontrado.', null, 404)
            : $this->sendResponse(ProductCatalogResource::make($model), 'ok');
    }

    public function update(ProductCatalogRequest $request, string $catalogo): JsonResponse
    {
        try {
            $id = $request->integer('id');
            $data = ProductCatalogData::fromValidated(
                $catalogo,
                $request->validated(),
                $this->registry,
            );
            $model = $this->repository->update($catalogo, $id, $data->attributes);

            return $model === null
                ? $this->sendError('Registro no encontrado.', null, 404)
                : $this->sendResponse(ProductCatalogResource::make($model), 'Registro actualizado correctamente.');
        } catch (Throwable $throwable) {
            $this->logError('ProductCatalogController update', $throwable);

            return $this->databaseError($throwable, 'No se pudo actualizar el registro.');
        }
    }

    public function destroy(string $catalogo, int $id): JsonResponse
    {
        try {
            return $this->repository->delete($catalogo, $id)
                ? $this->sendResponse(true, 'Registro eliminado correctamente.')
                : $this->sendError('Registro no encontrado.', null, 404);
        } catch (Throwable $throwable) {
            $this->logError('ProductCatalogController destroy', $throwable);

            return $this->databaseError($throwable, 'No se pudo eliminar el registro.');
        }
    }

    private function databaseError(Throwable $throwable, string $fallback): JsonResponse
    {
        if ($throwable instanceof QueryException && in_array((string) $throwable->getCode(), ['23000', '23503'], true)) {
            return $this->sendError(
                'El registro está duplicado o se encuentra relacionado con otros datos.',
                null,
                409,
            );
        }

        return $this->sendError($fallback, null, 500);
    }
}
