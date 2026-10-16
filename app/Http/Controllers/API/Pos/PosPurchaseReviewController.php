<?php

namespace App\Http\Controllers\API\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosPurchaseReviewRequest;
use App\Repositories\Pos\PosPurchaseReviewRepository;
use Illuminate\Http\JsonResponse;

class PosPurchaseReviewController extends Controller
{
    public function __construct(private readonly PosPurchaseReviewRepository $repository) {}

    public function index(PosPurchaseReviewRequest $request): JsonResponse
    {
        $filters = $request->validated();
        if (! $request->route('filtered')) {
            $filters = [];
        }

        return $this->sendResponse($this->repository->paginate($filters), 'ok', 200, true);
    }

    public function show(string $id): JsonResponse
    {
        $document = $this->repository->find($id);

        return $document === null ? $this->sendError('Compra POS no encontrada.', null, 404) : $this->sendResponse($document, 'ok');
    }

    public function options(PosPurchaseReviewRequest $request, string $kind): JsonResponse
    {
        $data = $request->validated();

        return $this->sendResponse($this->repository->options($kind, trim($data['search'] ?? '')), 'ok');
    }
}
