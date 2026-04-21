<?php

namespace App\Http\Controllers\API;

use App\DTOs\ClienteDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clientes\ClienteRequest;
use App\Http\Requests\Clientes\ClienteUpdatePhoneRequest;
use App\Http\Requests\Clientes\ClienteUpdateRequest;
use App\Http\Requests\Util\DefaultFilterRequest;
use App\Http\Resources\Clientes\ClienteResourceSingle;
use App\Http\Resources\Clientes\ClientesResource;
use App\Interfaces\Clientes\ClienteService;


class ClientesController extends Controller
{

    public function __construct(private ClienteService $clienteService) {}


    public function index()
    {
        $list = $this->clienteService->paginate();

        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron clientes.', 404);
        }
        return $this->sendResponse(
            ClienteResourceSingle::collection($list),
            'ok',
            200,
            true
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClienteRequest $request)
    {

        try {
            $dto = ClienteDTO::fromRequest($request->validated());
            $save = $this->clienteService->create($dto->toArray());

            if (!$save) {
                return $this->sendResponse(false, 'Error al crear el cliente.', 500);
            }

            return $this->sendResponse(true, 'Cliente creado exitosamente.', 201);
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Error inesperado: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cliente = $this->clienteService->findById($id);

        if (!$cliente) {
            return $this->sendResponse(null, 'Cliente no encontrado.', 404);
        }

        return $this->sendResponse(
            ClientesResource::make($cliente),
            'ok',
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClienteUpdateRequest $request)
    {

        try {
            $dto = ClienteDTO::fromRequest($request->validated());
            $save = $this->clienteService->update($dto->id, $dto->toArray());
            if (!$save) {
                return $this->sendResponse(false, 'Error al actualizar el cliente.', 500);
            }

            return $this->sendResponse(true, 'Cliente actualizado exitosamente.', 200);
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Error inesperado: ' . $th->getMessage(), 500);
        }
    }


    public function updatephone(ClienteUpdatePhoneRequest $request)
    {

        try {
            $dto = ClienteDTO::fromRequest($request->validated());
            $save = $this->clienteService->update($dto->id, $dto->toArray());
            if (!$save) {
                return $this->sendResponse(false, 'Error al actualizar el cliente.', 500);
            }
            return $this->sendResponse(true, 'Cliente actualizado exitosamente.', 200);
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Error inesperado: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $delete = $this->clienteService->delete($id);
            if (!$delete) {
                return $this->sendResponse(false, 'Error al eliminar el cliente.', 500);
            }
            return $this->sendResponse(true, 'Cliente eliminado exitosamente.', 200);
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Error inesperado: ' . $th->getMessage(), 500);
        }
    }

    public function filter(DefaultFilterRequest $request)
    {
        try {
            $filterModel = $request->toFilterModel();
            $list = $this->clienteService->filter($filterModel);

            if (!$list || $list->isEmpty()) {
                return $this->sendResponse(null, 'No se encontraron clientes.', 404);
            }
            return $this->sendResponse(
                ClienteResourceSingle::collection($list),
                'ok',
                200,
                true
            );
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Error al filtrar clientes: ' . $th->getMessage(), 500);
        }
    }

    public function checkphone(ClienteUpdatePhoneRequest $request)
    {
        $dto = ClienteDTO::fromRequest($request->validated());
        $active =  $this->clienteService->activephone($dto->telefono, $dto->id);

        if (!$active) {
            return $this->sendResponse(
                false,
                'El teléfono no ha sido utilizado en el último mes.',
                200
            );
        }
    }
}
