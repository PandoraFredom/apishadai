<?php

namespace App\Http\Controllers\API\Farma;

use App\Http\Controllers\Controller;
use App\Http\Resources\Farma\LotProductResource;
use App\Http\Resources\Farma\LotResource;
use App\Repositories\Farma\LotRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function __construct(private readonly LotRepository $repository) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->sendResponse(
            LotProductResource::collection($this->repository->paginateProducts(
                (int) ($validated['per_page'] ?? 30),
                isset($validated['search']) ? (string) $validated['search'] : null,
            )),
            'ok',
            200,
            true,
        );
    }

    public function productLots(int $product): JsonResponse
    {
        $lots = $this->repository->productLots($product);

        return $lots->isEmpty()
            ? $this->sendError('El producto no tiene lotes registrados.', null, 404)
            : $this->sendResponse(LotResource::collection($lots), 'Lotes encontrados.');
    }
}
