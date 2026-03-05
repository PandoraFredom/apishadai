<?php

namespace App\Http\Controllers\API;

use App\DTOs\MatchTokenDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\MatchTokenRequest;
use App\Http\Requests\MatchTokenUpdateRequest;
use App\Http\Resources\MatchTokenResource;
use App\Interfaces\Config\MatchTokensService;
use Illuminate\Http\JsonResponse;

class MatchTokensController extends Controller
{
    public function __construct(private MatchTokensService $matchTokenService) {}

    /**
     * Listar todos los tokens de coincidencia
     */
    public function index(): JsonResponse
    {
        try {
            $data = $this->matchTokenService->getAll();
            return $this->sendResponse(MatchTokenResource::collection($data), 'Lista de tokens');
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al obtener tokens: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crear un nuevo token de coincidencia
     */
    public function store(MatchTokenRequest $request): JsonResponse
    {
        try {
            $dto = MatchTokenDTO::onCreate($request->validated());

            $success = $this->matchTokenService->create($dto->toArray());

            if (!$success) {
                return $this->sendResponse(null, 'Error al crear el token', 500);
            }

            return $this->sendResponse(null, 'Token creado exitosamente', 201);
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al crear token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener un token por ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $token = $this->matchTokenService->findById($id);

            if (!$token) {
                return $this->sendResponse(null, 'Token no encontrado', 404);
            }

            return $this->sendResponse(new MatchTokenResource($token), 'Token encontrado');
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al obtener token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar un token
     */
    public function update(int $id, MatchTokenUpdateRequest $request): JsonResponse
    {
        try {
            $token = $this->matchTokenService->findById($id);

            if (!$token) {
                return $this->sendResponse(null, 'Token no encontrado', 404);
            }

            $dto = MatchTokenDTO::fromUpdateRequest(array_merge(['id' => $id], $request->validated()));

            $success = $this->matchTokenService->update($id, $dto->toUpdateArray());

            if (!$success) {
                return $this->sendResponse(null, 'Error al actualizar el token', 500);
            }

            return $this->sendResponse(null, 'Token actualizado exitosamente');
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al actualizar token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar un token
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $token = $this->matchTokenService->findById($id);

            if (!$token) {
                return $this->sendResponse(null, 'Token no encontrado', 404);
            }

            $success = $this->matchTokenService->delete($id);

            if (!$success) {
                return $this->sendResponse(null, 'Error al eliminar el token', 500);
            }

            return $this->sendResponse(null, 'Token eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al eliminar token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener tokens por usuario
     */
    public function getByUserId(int $userId): JsonResponse
    {
        try {
            $tokens = $this->matchTokenService->getByUserId($userId);

            if ($tokens->isEmpty()) {
                return $this->sendResponse([], 'Sin tokens para este usuario');
            }

            return $this->sendResponse(MatchTokenResource::collection($tokens), 'Tokens del usuario');
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al obtener tokens: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar todos los tokens de un usuario
     */
    public function deleteByUserId(int $userId): JsonResponse
    {
        try {
            $success = $this->matchTokenService->deleteByUserId($userId);

            if (!$success) {
                return $this->sendResponse(null, 'No hay tokens para eliminar', 404);
            }

            return $this->sendResponse(null, 'Tokens del usuario eliminados exitosamente');
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al eliminar tokens: ' . $e->getMessage(), 500);
        }
    }
}
