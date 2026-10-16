<?php

namespace App\Http\Controllers\API\Farma\Productos;

use App\DTO\Farma\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farma\Productos\FamiliaFilterRequest;
use App\Http\Requests\Farma\Productos\LaboratorioFilterRequest;
use App\Http\Requests\Farma\Productos\PrincipalActivoFilterRequest;
use App\Http\Requests\Farma\Productos\PrincipalActivoListRequest;
use App\Http\Requests\Farma\Productos\ProductFilterRequest;
use App\Http\Requests\Farma\Productos\ProductParentLookupRequest;
use App\Http\Requests\Farma\Productos\ProductPrincipleRemoveRequest;
use App\Http\Requests\Farma\Productos\ProductPrincipleRequest;
use App\Http\Requests\Farma\Productos\ProductRequest;
use App\Http\Requests\Farma\Productos\ProdUnidadFilterRequest;
use App\Http\Resources\Farma\FamiliaResource;
use App\Http\Resources\Farma\PrincipalActivoResource;
use App\Http\Resources\Farma\ProdCategoriaResource;
use App\Http\Resources\Farma\ProdEstadoResource;
use App\Http\Resources\Farma\ProductResource;
use App\Http\Resources\Farma\ProdUnidadResource;
use App\Http\Resources\Laboratorios\LaboratorioResource;
use App\Interfaces\Farma\FamiliaService;
use App\Interfaces\Farma\PrincipalActivoService;
use App\Interfaces\Farma\ProdCategoriaService;
use App\Interfaces\Farma\ProdEstadoService;
use App\Interfaces\Farma\ProductService;
use App\Interfaces\Farma\ProdUnidadService;
use App\Interfaces\Laboratorios\LaboratorioService;
use App\Models\Farma\Producto;
use App\Utils\Services\Base64UtilityService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $repository,
        private readonly Base64UtilityService $base64Utility,
        private readonly LaboratorioService $laboratorios,
        private readonly FamiliaService $familias,
        private readonly ProdUnidadService $unidades,
        private readonly ProdEstadoService $estados,
        private readonly ProdCategoriaService $categorias,
        private readonly PrincipalActivoService $principios,
    ) {}

    public function padres(ProductParentLookupRequest $request): JsonResponse
    {
        $data = $request->validated();
        $search = trim($data['search'] ?? '');
        $query = Producto::query()->select(['id', 'codigo', 'descripcion']);
        if ($search !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw("codigo LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("descripcion LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("codigobar LIKE ? ESCAPE '!'", [$pattern]);
            });
        }
        if (isset($data['exclude'])) {
            $query->where('id', '<>', $data['exclude']);
        }
        $page = $query->orderBy('descripcion')->orderBy('id')->paginate(30);
        $page->through(fn ($product): array => ['id' => (string) $product->id, 'codigo' => $product->codigo, 'descripcion' => $product->descripcion]);

        return $this->sendResponse($page, 'ok', 200, true);
    }

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

    public function filterProductos(ProductFilterRequest $request): JsonResponse
    {
        return $this->sendResponse(
            ProductResource::collection($this->repository->filterProducts($request->toFilterModel())),
            'ok',
            200,
            true,
        );
    }

    public function laboratorios(Request $request): JsonResponse
    {
        return $this->sendResponse(
            LaboratorioResource::collection($request->boolean('all') ? $this->laboratorios->getAll() : $this->laboratorios->paginate()),
            'ok',
            200,
            true,
        );
    }

    public function filterLaboratorios(LaboratorioFilterRequest $request): JsonResponse
    {
        return $this->sendResponse(
            LaboratorioResource::collection($this->laboratorios->filterAll($request->toFilterModel(), true)),
            'ok',
            200,
            true,
        );
    }

    public function familias(Request $request): JsonResponse
    {
        return $this->sendResponse(
            FamiliaResource::collection($request->boolean('all') ? $this->familias->getAll() : $this->familias->paginate()),
            'ok',
            200,
            true,
        );
    }

    public function filterFamilias(FamiliaFilterRequest $request): JsonResponse
    {
        return $this->sendResponse(
            FamiliaResource::collection($this->familias->filterAll($request->toFilterModel(), true)),
            'ok',
            200,
            true,
        );
    }

    public function unidades(Request $request): JsonResponse
    {
        return $this->sendResponse(
            ProdUnidadResource::collection($request->boolean('all') ? $this->unidades->getAll() : $this->unidades->paginate()),
            'ok',
            200,
            true,
        );
    }

    public function filterUnidades(ProdUnidadFilterRequest $request): JsonResponse
    {
        return $this->sendResponse(
            ProdUnidadResource::collection($this->unidades->filterAll($request->toFilterModel(), true)),
            'ok',
            200,
            true,
        );
    }

    public function estados(Request $request): JsonResponse
    {
        return $this->sendResponse(
            ProdEstadoResource::collection($request->boolean('all') ? $this->estados->getAll() : $this->estados->paginate()),
            'ok',
            200,
            true,
        );
    }

    public function categorias(Request $request): JsonResponse
    {
        return $this->sendResponse(
            ProdCategoriaResource::collection($request->boolean('all') ? $this->categorias->getAll() : $this->categorias->paginate()),
            'ok',
            200,
            true,
        );
    }

    public function todosPrincipiosActivos(PrincipalActivoListRequest $request): JsonResponse
    {
        return $this->sendResponse(
            PrincipalActivoResource::collection($request->boolean('all') ? $this->principios->getAll() : $this->principios->paginate(15)),
            'ok',
            200,
            true,
        );
    }

    public function filterPrincipiosActivos(PrincipalActivoFilterRequest $request): JsonResponse
    {
        return $this->sendResponse(
            PrincipalActivoResource::collection($this->principios->filterAll($request->toFilterModel())),
            'ok',
        );
    }

    public function principiosActivos(int $id): JsonResponse
    {
        $principles = $this->repository->getActivePrinciples($id);
        if ($principles === null) {
            return $this->sendError('Producto no encontrado.', null, 404);
        }

        return $this->sendResponse(PrincipalActivoResource::collection($principles), 'ok');
    }

    public function agregarPrincipioActivo(ProductPrincipleRequest $request, int $id): JsonResponse
    {
        try {
            return $this->repository->addActivePrinciple($id, (int) $request->validated('pactivo'))
                ? $this->sendResponse(true, 'Principio activo asociado correctamente.', 201)
                : $this->sendError('Producto no encontrado.', null, 404);
        } catch (Throwable $throwable) {
            $this->logError('ProductController agregarPrincipioActivo', $throwable);

            return $this->databaseError($throwable, 'No se pudo asociar el principio activo.');
        }
    }

    public function eliminarPrincipioActivo(ProductPrincipleRemoveRequest $request, int $id): JsonResponse
    {
        try {
            return $this->repository->removeActivePrinciple($id, (int) $request->validated('pactivo'))
                ? $this->sendResponse(true, 'Principio activo eliminado del producto correctamente.')
                : $this->sendError('Asociación no encontrada.', null, 404);
        } catch (Throwable $throwable) {
            $this->logError('ProductController eliminarPrincipioActivo', $throwable);

            return $this->databaseError($throwable, 'No se pudo eliminar el principio activo del producto.');
        }
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

            if (! $this->repository->create($data, $productData->principiosActivos ?? [])) {
                return $this->sendError('No se pudo crear el producto.', false, 422);
            }

            return $this->sendResponse(
                true,
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
        $product = $this->repository->findById($id);

        return $product === null
            ? $this->sendError('Producto no encontrado.', null, 404)
            : $this->sendResponse(ProductResource::make($product), 'ok');
    }

    public function update(ProductRequest $request, int $id): JsonResponse
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

            $updated = $this->repository->update(
                $id,
                $data,
                $productData->principiosActivos,
            );

            return ! $updated
                ? $this->sendError('No se pudo actualizar el producto.', false, 404)
                : $this->sendResponse(true, 'Producto actualizado correctamente.');
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
        if ($throwable instanceof ValidationException) {
            return response()->json(['code' => 422, 'message' => 'Revisa el producto padre.', 'errors' => $throwable->errors()], 422);
        }
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
