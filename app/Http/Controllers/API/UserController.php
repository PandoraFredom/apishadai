<?php

namespace App\Http\Controllers\API;

use App\DTOs\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\RolesResource;
use App\Http\Resources\UserEstadosResource;
use App\Http\Resources\UserResource;
use App\Interfaces\Config\UserRepositoryInterface;
use App\Services\EncryptionService;

class UserController extends Controller
{

    public function __construct(private UserRepositoryInterface $service,
    private EncryptionService $encService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = $this->service->getAll();
        if (!$users) {
            return $this->sendResponse(
                null,
                'No se encontraron usuarios',
                404
            );
        }

        return $this->sendResponse(
            UserResource::collection($users),
            'ok',
            200,
            false

        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $dto = UserDTO::fromRequest($request->all());
        $data = [
            'nombre' => $dto->nombre,
            'name' => $this->encService->genHash($dto->name),
            'email' => $dto->email,
            'password' => bcrypt($dto->password),
            'rol' => $dto->rol,
            'estado' => $dto->estado,
        ];



        $created = $this->service->create($data);

        if (!$created) {
            return $this->sendResponse(
                false,
                'No se pudo crear el usuario',
                500
            );
        }
        return $this->sendResponse(
            true,
            'Usuario creado con exito',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = $this->service->findById($id);
        if (!$user) {
            return $this->sendResponse(
                null,
                'Usuario no encontrado',
                404
            );
        }

        return $this->sendResponse(
            UserResource::make($user),
            'ok',
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request)
    {
        $dto = UserDTO::fromUpdateRequest($request->all());

        $updated = $this->service->update($dto->id, $dto->toArray());

        if (!$updated) {
            return $this->sendResponse(
                false,
                'No se pudo actualizar el usuario',
                500
            );
        }
        return $this->sendResponse(
            true,
            'Usuario actualizado con exito',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return $this->sendResponse(
                false,
                'No se pudo eliminar el usuario',
                500
            );
        }
        return $this->sendResponse(
            true,
            'Usuario eliminado con exito',
            200
        );
    }

    public function rolList()
    {
        $roles = $this->service->get_rolList();
        if (!$roles) {
            return $this->sendResponse(
                null,
                'No se encontraron roles',
                404
            );
        }

        return $this->sendResponse(
            RolesResource::collection($roles),
            'ok',
            200,
            false

        );
    }
    public function estadosList()
    {
        $estados = $this->service->get_estadoList();
        if (!$estados) {
            return $this->sendResponse(
                null,
                'No se encontraron estados de usuario',
                404
            );
        }

        return $this->sendResponse(
            UserEstadosResource::collection($estados),
            'ok',
            200,
            false
        );
    }
}
