<?php

namespace App\Http\Controllers\API;

use App\DTOs\ClienteDTO;
use App\DTOs\TicketDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Promos\TicketRequest;
use App\Http\Requests\Clientes\ClienteRequest;
use App\Http\Requests\Clientes\ClienteUpdatePhoneRequest;
use App\Http\Requests\Util\FilterRequest;
use App\Http\Resources\Clientes\ClienteResourceSingle;
use App\Http\Resources\DepartamentoResource;
use App\Http\Resources\tiketsResource;
use App\Http\Resources\Ubicacion\MunicipiosResourceSingle;
use App\Interfaces\Promos\TicketService;
use App\Models\tikets;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use function is_numeric;

class tiketsController extends Controller
{


    public function __construct(private TicketService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = $this->service->getList();

        return $this->sendResponse(
            tiketsResource::collection($list),
            'ok',
            200,
            true
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketRequest $request)
    {

        try {
            $dto = TicketDTO::fromRequest($request->validated());

            $data = [
                'promocion' => $dto->promocion,
                'cliente' => $dto->cliente,
                'ntiket' => tikets::count('id') + 1,
                'usuario' => Auth::user()->id,
                'stock' => $dto->stock
            ];

            $ticket = tikets::create($data);
            if (!$ticket) {
                return $this->sendResponse(null, 'Error al crear el tiket', 500);
            }


            $pdf = PDF::loadView('tickets.print', compact('ticket'));
            $pdf->setPaper([0, 0, 226.77, 600], 'portrait');

            $pdfContent = $pdf->output();

            // set 200 response
            return $this->sendResponse([
                'filename' => "ticket_{$ticket->id}.pdf",
                'mime' => 'application/pdf',
                'base64' => base64_encode($pdfContent),
            ], 'Ticket created', 200);
        } catch (\Throwable $th) {
            Log::info('Error creating ticket: ' . request()->getContent());
            return $this->sendResponse(null, 'Error al crear el tiket: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.0
     */
    public function show(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return $this->sendResponse(null, 'Not Implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->sendResponse(null, 'Not Implemented', 501);
    }


    public function getpromo()
    {
        $promo = $this->service->getActivePromo();
        if (!$promo) {
            return $this->sendResponse(null, 'No hay Sorteos disponibles.', 404);
        }

        return $this->sendResponse($promo, 'ok', 200);
    }

    public function get_clientesList()
    {
        try {
            $clientes = $this->service->get_clientesList();

            return $this->sendResponse(
                ClienteResourceSingle::collection($clientes),
                'ok',
                200,
                true
            );
        } catch (\Throwable $th) {
            return $this->sendResponse(null, 'Error al obtener clientes: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Filter clients by term
     */
    public function filter_clientes(FilterRequest $request)
    {
        try {
            $filterModel = $request->toFilterModel();

            $clientes = $this->service->filter_clientes($filterModel);

            return $this->sendResponse(
                ClienteResourceSingle::collection($clientes),
                'ok',
                200,
                true
            );
        } catch (\Throwable $th) {
            return $this->sendResponse(null, 'Error al filtrar clientes: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Check if phone is active for a client
     */
    public function activephone(int $id)
    {
        try {

            $active =  $this->service->activephone('', $id);

            return $this->sendResponse(
                $active,
                'ok'
            );
        } catch (\Throwable $th) {
            return $this->sendResponse(null, 'Error al verificar teléfono: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Create a new client
     */
    public function create_cliente(ClienteRequest $request)
    {
        try {
            $dto = ClienteDTO::fromRequest($request->validated());

            $cliente = $this->service->create_cliente($dto->toArray());

            if (!$cliente) {
                return $this->sendResponse(null, 'Error al crear el cliente', 500);
            }

            return $this->sendResponse(
                $cliente,
                'Cliente creado correctamente'
            );
        } catch (\Throwable $th) {
            return $this->sendResponse(null, 'Error al crear cliente: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Update client phone
     */
    public function update_phone_cliente(ClienteUpdatePhoneRequest $request)
    {
        try {
            $dto =  ClienteDTO::fromRequest($request->validated());

            $data = [
                'telefono' => $dto->telefono,
                'phone_updated_at' => now()
            ];
            $cliente = $this->service->update_phone_cliente($dto->id, $data);
            if (!$cliente) {
                return $this->sendResponse(false, 'Error al actualizar el teléfono del cliente', 500);
            }
            return $this->sendResponse(
                $cliente,
                'ok'
            );
        } catch (\Throwable $th) {
            return $this->sendResponse(null, 'Error al actualizar cliente: ' . $th->getMessage(), 500);
        }
    }

    public function get_departamentosList()
    {
        try {
            $departamentos = $this->service->get_departamentosList();

            return $this->sendResponse(
                DepartamentoResource::collection($departamentos),
                'ok',
                200,
                false
            );
        } catch (\Throwable $th) {
            return $this->sendResponse(null, 'Error al obtener departamentos: ' . $th->getMessage(), 500);
        }
    }

    public function get_municipiosList(int $id)
    {
        try {

            if ($id <= 0 || !is_numeric($id) || $id === null) {
                return $this->sendResponse(null, 'El ID del departamento es inválido.', 400);
            }

            $municipios = $this->service->get_municipiosList($id);

            return $this->sendResponse(
                MunicipiosResourceSingle::collection($municipios),
                'ok',
                200,
                false
            );
        } catch (\Throwable $th) {
            return $this->sendResponse(null, 'Error al obtener municipios: ' . $th->getMessage(), 500);
        }
    }
}
