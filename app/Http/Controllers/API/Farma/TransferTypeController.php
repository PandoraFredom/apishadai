<?php

namespace App\Http\Controllers\API\Farma;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farma\Transferencias\TransferTypeRequest;
use App\Repositories\Farma\TransferTypeRepository;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferTypeController extends Controller
{
    public function __construct(private readonly TransferTypeRepository $repository) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $records = $this->repository->paginate((int) ($validated['per_page'] ?? 30), trim($validated['search'] ?? ''))
            ->through(fn ($record): array => ['id' => (int) $record->id, 'descripcion' => $record->descripcion]);

        return $this->sendResponse($records, 'ok', 200, true);
    }

    public function store(TransferTypeRequest $request): JsonResponse
    {
        return $this->sendResponse($this->repository->create($request->validated('descripcion')), 'Tipo de transferencia creado correctamente.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->repository->find($id);

        return $record === null ? $this->sendError('Tipo de transferencia no encontrado.', null, 404) : $this->sendResponse($record, 'ok');
    }

    public function update(TransferTypeRequest $request): JsonResponse
    {
        $record = $this->repository->update($request->integer('id'), $request->validated('descripcion'));

        return $record === null ? $this->sendError('Tipo de transferencia no encontrado.', null, 404) : $this->sendResponse($record, 'Tipo de transferencia actualizado correctamente.');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            return $this->repository->delete($id)
                ? $this->sendResponse(true, 'Tipo de transferencia eliminado correctamente.')
                : $this->sendError('Tipo de transferencia no encontrado.', null, 404);
        } catch (DomainException $exception) {
            return $this->sendError($exception->getMessage(), null, 409);
        } catch (QueryException) {
            return $this->sendError('El tipo está relacionado con transferencias y no se puede eliminar.', null, 409);
        }
    }
}
