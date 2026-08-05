<?php

namespace App\Http\Controllers\API\Farma\Productos;

use App\DTOs\Farma\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farma\Productos\ProductRequest;
use App\Http\Resources\Farma\ProductResource;
use App\Repositories\Farma\ProductRepository;
use App\Utils\Services\Base64UtilityService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly Base64UtilityService $base64Utility,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'laboratorio' => ['nullable', 'integer', 'min:1', 'exists:laboratorios,id'],
            'proveedor' => ['nullable', 'integer', 'min:1', 'exists:proveedores,id'],
        ]);

        return $this->sendResponse(
            ProductResource::collection($this->repository->paginate(
                (int) ($validated['per_page'] ?? 30),
                isset($validated['search']) ? (string) $validated['search'] : null,
                isset($validated['laboratorio']) ? (int) $validated['laboratorio'] : null,
                isset($validated['proveedor']) ? (int) $validated['proveedor'] : null,
            )),
            'ok',
            200,
            true,
        );
    }

    public function options(): JsonResponse
    {
        return $this->sendResponse($this->repository->options(), 'ok');
    }

    public function store(ProductRequest $request): JsonResponse
    {
        try {
            $productData = ProductData::fromValidated($request->validated());
            $data = $productData->toArray();

            if (isset($data['imagen'])) {
                $data['imagen'] = $this->sanitizeImage((string) $data['imagen']);

                if ($data['imagen'] === null) {
                    return $this->sendError('Imagen inválida.', null, 422);
                }
            }

            return $this->sendResponse(
                ProductResource::make($this->repository->create(
                    $data,
                    $productData->principiosActivos ?? [],
                )),
                'Producto creado correctamente.',
                201,
            );
        } catch (Throwable $throwable) {
            $this->logError('ProductController store', $throwable);

            return $this->databaseError($throwable, 'No se pudo crear el producto.');
        }
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->repository->find($id);

        return $product === null
            ? $this->sendError('Producto no encontrado.', null, 404)
            : $this->sendResponse(ProductResource::make($product), 'ok');
    }

    public function update(ProductRequest $request): JsonResponse
    {
        try {
            $id = $request->integer('id');
            $productData = ProductData::fromValidated($request->validated());
            $data = $productData->toArray();

            if (isset($data['imagen'])) {
                $data['imagen'] = $this->sanitizeImage((string) $data['imagen']);

                if ($data['imagen'] === null) {
                    return $this->sendError('Imagen inválida.', null, 422);
                }
            }

            $product = $this->repository->update(
                $id,
                $data,
                $productData->principiosActivos,
            );

            return $product === null
                ? $this->sendError('Producto no encontrado.', null, 404)
                : $this->sendResponse(ProductResource::make($product), 'Producto actualizado correctamente.');
        } catch (Throwable $throwable) {
            $this->logError('ProductController update', $throwable);

            return $this->databaseError($throwable, 'No se pudo actualizar el producto.');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            return $this->repository->delete($id)
                ? $this->sendResponse(true, 'Producto eliminado correctamente.')
                : $this->sendError('Producto no encontrado.', null, 404);
        } catch (Throwable $throwable) {
            $this->logError('ProductController destroy', $throwable);

            return $this->databaseError($throwable, 'No se pudo eliminar el producto.');
        }
    }

    public function getImage(int $id): JsonResponse
    {
        try {
            $stored = $this->repository->getImage($id);

            if ($stored === null) {
                return $this->sendError('Imagen no encontrada.', null, 404);
            }

            $image = $this->base64Utility->validate($stored);

            if ($image === null) {
                $image = $this->base64Utility->sanitize($stored);

                if ($image !== null) {
                    $this->repository->update($id, ['imagen' => $image]);
                }
            }

            return $image === null
                ? $this->sendError('La imagen almacenada no es válida.', null, 422)
                : $this->sendResponse(['image' => $image], 'ok');
        } catch (Throwable $throwable) {
            $this->logError('ProductController getImage', $throwable);

            return $this->sendError('No se pudo obtener la imagen.', null, 500);
        }
    }

    private function sanitizeImage(string $image): ?string
    {
        return $this->base64Utility->sanitize($image);
    }

    private function databaseError(Throwable $throwable, string $fallback): JsonResponse
    {
        if ($throwable instanceof QueryException && in_array((string) $throwable->getCode(), ['23000', '23503'], true)) {
            return $this->sendError(
                'El producto está duplicado o se encuentra relacionado con otros datos.',
                null,
                409,
            );
        }

        return $this->sendError($fallback, null, 500);
    }
}
