<?php

namespace App\Http\Controllers\API;

use App\DTOs\StockDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockRequest;
use App\Http\Resources\Stock\StocksResource;
use App\Interfaces\Config\StockRepositoryInterface;
use Illuminate\Http\Request;


class StocksController extends Controller
{

    public function __construct(private StockRepositoryInterface $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = $this->service->getAll();
        if (!$list) {
            return $this->sendResponse(null, 'No data found', 404);
        }
        return $this->sendResponse(StocksResource::collection($list), 'ok', 200, false);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StockRequest $request)
    {
        try {
             $dto = StockDTO::fromRequest($request->validated());
        $created = $this->service->create($dto->toArray());
        if (!$created) {
            return $this->sendResponse(false, 'Error creating stock', 500);
        }
        return $this->sendResponse(true, 'Stock created successfully', 201);
        } catch (\Throwable $th) {
            $this->logError('StocksController store', $th);
            return $this->sendResponse(false, 'Error al crear stock.', 500);
        }



    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = $this->service->findById($id);
        if (!$item) {
            return $this->sendResponse(null, 'Stock not found', 404);
        }
        return $this->sendResponse(StocksResource::make($item), 'ok');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
       // not implemented
       return $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleted = $this->service->delete($id);
        if (!$deleted) {
            return $this->sendResponse(false, 'Stock no disponible para eliminar.', 500);
        }
        return $this->sendResponse(true, 'Stock deleted successfully', 200);
    }
}
