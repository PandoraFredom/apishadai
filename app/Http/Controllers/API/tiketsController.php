<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\tiketsResource;
use App\Models\Promociones;
use App\Models\tikets;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class tiketsController extends Controller
{
    private $encService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encService = $encryptionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->sendResponse(null, 'Not Implemented', 501);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $uuid)
    {
        // Validar request
        try {
            $validate = Validator::make($request->all(), [
                'promocion.id' => 'required|integer|exists:promociones,id',
                'cliente.id' => 'required|integer|exists:clientes,id',
                'stock.id' => 'required|integer|exists:stocks,id',
            ]);

            if ($validate->fails()) {
                return $this->sendResponse(null,  $validate->errors()->first(), 422);
            }

            // check if uuid is not null 
            if ($uuid == null) {
                return $this->sendResponse(null, 'UUID is required', 422);
            }

            // decrypt uuid
            $id = $this->encService->decrypt($uuid);


            // Verificar estado de la promoción
            $promoId = $request->input('promocion.id');
            if (!$this->checkPromocionStatus($promoId)) {
                return $this->sendResponse(null, 'La promoción no está activa:', 422);
            }

            // Obtener datos del request
            $promocion = $request->input('promocion.id');
            $cliente = $request->input('cliente.id');
            $stock = $request->input('stock.id');


            // Obtener número de tiket por promoción
            $ntiket = tikets::where('promocion', $promocion)->count() + 1;

            // Crear el tiket
            $ticket = tikets::create([
                'promocion' => $promocion,
                'cliente' => $cliente,
                'ntiket' => $ntiket,
                'usuario' => $id,
                'stock' => $stock,
            ]);

            if (!$ticket) {
                return $this->sendResponse(null, 'Error creating ticket', 500);
            }

            // Crear archivo para imprimir
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
            return $this->sendResponse(null, "Error creating ticket:{$id}", 500);
        }
    }

    /**
     * Display the specified resource.0
     */
    public function show(string $id)
    {
        $ticket = tikets::find($id);
        if (!$ticket) {
            return $this->sendResponse(null, 'Ticket not found', 404);
        }
        return $this->sendResponse(TiketsResource::make($ticket), 'Ticket found');
    }

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


    private function checkPromocionStatus($pId)
    {
        // Verificar si la promoción existe y si esta activo 
        $promocion = Promociones::find($pId);
        if (!$promocion) {
            return false; // Promoción no existe
        }
        if ($promocion->Estado->descripcion !== 'ACTIVO') {
            return false; // Promoción no está activa
        }
        return true; // Promoción está activa
    }


    public function filter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' => 'required|string',
            'filterItem.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }

        $query = tikets::query();

        // Agrupar filtros por columna base
        $filters = collect($request->filterItem)->groupBy(fn($item) => explode('.', $item['key'])[0]);

        // Solo columnas permitidas
        $allowed = ['promocion', 'cliente', 'usuario', 'stock', 'created_at'];

        foreach ($filters as $column => $filterGroup) {
            if (!in_array($column, $allowed)) {
                continue; // Ignorar columnas no permitidas
            }

            $start = $filterGroup->firstWhere('key', "$column.start");
            $end = $filterGroup->firstWhere('key', "$column.end");

            if ($start && $end && $column === 'created_at') {
                // Rango de fechas
                $query->whereBetween($column, [
                    \Carbon\Carbon::parse($start['value'])->startOfDay(),
                    \Carbon\Carbon::parse($end['value'])->endOfDay(),
                ]);
            } else {
                foreach ($filterGroup as $filter) {
                    $key = $filter['key'];
                    $value = $filter['value'];

                    switch ($column) {
                        case 'promocion':
                        case 'cliente':
                        case 'usuario':
                        case 'stock':
                            $query->where($column, $value);
                            break;
                        case 'created_at':
                            // Fecha exacta
                            $query->whereDate($column, \Carbon\Carbon::parse($value)->toDateString());
                            break;
                    }
                }
            }
        }

        $tikets = $query->get();

        if ($tikets->isEmpty()) {
            return $this->sendResponse(null, 'No se encontraron tickets', 404);
        }

        return $this->sendResponse(tiketsResource::collection($tikets), 'Tickets encontrados');
    }
}
